<?php

namespace App\Services\Accounting;

use App\Enums\AccountingVoucherType;
use App\Models\Account;
use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\Bilty;
use App\Models\Branch;
use App\Models\Challan;
use App\Models\DeliveryReceipt;
use App\Models\Invoice;
use App\Models\InwardRegister;
use App\Models\MainChallan;
use App\Models\Party;
use App\Models\Voucher;
use App\Support\GstCalculator;
use Illuminate\Database\Eloquent\Model;

/**
 * Turns one business document event into balanced voucher lines. Amounts are
 * handled in integer paise; a mapping returns null when there is nothing to post
 * or the document lacks what is needed (e.g. bank mode without a bank account).
 */
class AccountingMapper
{
    /**
     * Accounting events per document class, in posting order.
     */
    public const EVENTS = [
        Invoice::class => ['invoice.freight'],
        Bilty::class => ['bilty.paid_receipt', 'bilty.advance_receipt', 'bilty.cartage_cash', 'bilty.cartage_due'],
        DeliveryReceipt::class => ['delivery_receipt.collection'],
        InwardRegister::class => ['inward.agent_crossing'],
        Challan::class => ['challan.agent_settlement'],
        MainChallan::class => ['main_challan.agent_settlement'],
        Voucher::class => ['legacy_voucher.payment_receipt'],
    ];

    /**
     * The single event each class posted before per-event source links existed.
     */
    public const LEGACY_EVENTS = [
        Invoice::class => 'invoice.freight',
        Bilty::class => 'bilty.cartage_due',
        InwardRegister::class => 'inward.agent_crossing',
        Challan::class => 'challan.agent_settlement',
        MainChallan::class => 'main_challan.agent_settlement',
    ];

    public const EVENT_LABELS = [
        'invoice.freight' => 'Invoice freight',
        'bilty.paid_receipt' => 'Paid bilty receipt',
        'bilty.advance_receipt' => 'Bilty advance received',
        'bilty.cartage_cash' => 'Bilty cash cartage',
        'bilty.cartage_due' => 'Bilty cartage due',
        'delivery_receipt.collection' => 'Delivery collection',
        'inward.agent_crossing' => 'Inward crossing freight',
        'challan.agent_settlement' => 'Challan agent settlement',
        'main_challan.agent_settlement' => 'Main challan agent settlement',
        'legacy_voucher.payment_receipt' => 'Legacy payment/receipt voucher',
    ];

    private const MAX_ROUND_OFF_PAISE = 100;

    public function __construct(
        private ChartOfAccountsService $chartOfAccounts,
        private EntityAccountLinker $linker,
    ) {}

    /**
     * @return list<string>
     */
    public function eventsFor(Model $document): array
    {
        return self::EVENTS[$document::class] ?? [];
    }

    public function legacyEventFor(Model $document): ?string
    {
        return self::LEGACY_EVENTS[$document::class] ?? null;
    }

    /**
     * @return array{voucher_type: AccountingVoucherType, narration: ?string, lines: list<array{account_id:int,debit:string,credit:string,narration:?string}>}|null
     */
    public function map(string $eventKey, Model $document): ?array
    {
        $this->chartOfAccounts->ensureDefaults((int) $document->company_id);

        return match ($eventKey) {
            'invoice.freight' => $document instanceof Invoice ? $this->mapInvoiceFreight($document) : null,
            'bilty.paid_receipt' => $document instanceof Bilty ? $this->mapBiltyPaidReceipt($document) : null,
            'bilty.advance_receipt' => $document instanceof Bilty ? $this->mapBiltyAdvanceReceipt($document) : null,
            'bilty.cartage_cash' => $document instanceof Bilty ? $this->mapBiltyCartageCash($document) : null,
            'bilty.cartage_due' => $document instanceof Bilty ? $this->mapBiltyCartageDue($document) : null,
            'delivery_receipt.collection' => $document instanceof DeliveryReceipt ? $this->mapDeliveryCollection($document) : null,
            'inward.agent_crossing' => $document instanceof InwardRegister ? $this->mapInwardAgentCrossing($document) : null,
            'challan.agent_settlement' => $document instanceof Challan
                ? $this->mapAgentSettlement($document, 'Challan '.$document->challan_number, (float) ($document->cash_payment ?? 0))
                : null,
            'main_challan.agent_settlement' => $document instanceof MainChallan
                ? $this->mapAgentSettlement($document, 'Main Challan '.$document->main_challan_number, (float) ($document->cash_paid ?? 0))
                : null,
            'legacy_voucher.payment_receipt' => $document instanceof Voucher ? $this->mapLegacyVoucher($document) : null,
            default => null,
        };
    }

    /**
     * Dr party for the invoice total; Cr freight income, and GST output for the
     * transporter-liable GST included in net_amount.
     */
    private function mapInvoiceFreight(Invoice $invoice): ?array
    {
        $net = $this->paise($invoice->net_amount);
        if ($net <= 0) {
            return null;
        }

        $taxable = $this->paise($invoice->total_amount);
        $gst = max(min($net - $taxable, $this->paise($invoice->gst_amount), $net), 0);
        $income = $net - $gst;

        $companyId = (int) $invoice->company_id;
        $party = $this->partyLedger($invoice->party_id, $companyId);

        return $this->finalize(AccountingVoucherType::Sales, 'Invoice '.$invoice->invoice_no, $companyId, [
            $this->line($party->id, $net, 0, 'Invoice receivable'),
            $this->line($this->system($companyId, 'freight_income'), 0, $income, 'Freight income'),
            ...$this->gstOutputLines($companyId, $gst, $this->branchStateId($invoice->branch_id), $this->partyStateId($invoice->party_id)),
        ]);
    }

    /**
     * Paid bilty: Dr Cash/Bank for the amount received, Dr consignor for any
     * advance already received; Cr freight, other charges and GST output.
     */
    private function mapBiltyPaidReceipt(Bilty $bilty): ?array
    {
        if ($bilty->payment_type !== 'Paid') {
            return null;
        }

        $companyId = (int) $bilty->company_id;
        $receivedIn = $this->cashOrBank($bilty->payment_mode, $bilty->bank_account_id, $companyId);
        if (! $receivedIn) {
            return null;
        }

        $breakdown = $bilty->gstBreakdown();
        $charges = $this->paise($breakdown['charges_total']);
        $freight = min($this->paise($bilty->freight), $charges);
        $gstInTotal = $this->paise($breakdown['gst_in_total']);
        $advance = $this->paise($bilty->less_advance_received);

        if ($charges <= 0 || $gstInTotal > $charges) {
            return null;
        }

        $gstSplit = $gstInTotal > 0
            ? ['cgst' => $this->paise($breakdown['cgst_amount']), 'sgst' => $this->paise($breakdown['sgst_amount']), 'igst' => $this->paise($breakdown['igst_amount'])]
            : ['cgst' => 0, 'sgst' => 0, 'igst' => 0];

        return $this->finalize(AccountingVoucherType::Receipt, 'Bilty '.$bilty->bilty_number.' paid', $companyId, [
            $this->line($receivedIn->id, $charges + $gstInTotal - $advance, 0, 'Freight received'),
            $this->line($this->partyLedger($bilty->consignor_id, $companyId)->id, $advance, 0, 'Advance adjusted'),
            $this->line($this->system($companyId, 'freight_income'), 0, $freight, 'Freight income'),
            $this->line($this->system($companyId, 'bilty_charges_income'), 0, $charges - $freight, 'Bilty charges'),
            ...$this->splitGstLines($companyId, $gstSplit),
        ]);
    }

    private function mapBiltyAdvanceReceipt(Bilty $bilty): ?array
    {
        $advance = $this->paise($bilty->less_advance_received);
        if ($advance <= 0) {
            return null;
        }

        $companyId = (int) $bilty->company_id;
        $receivedIn = $this->cashOrBank($bilty->advance_payment_mode, $bilty->advance_bank_account_id, $companyId);
        if (! $receivedIn) {
            return null;
        }

        return $this->finalize(AccountingVoucherType::Receipt, 'Bilty '.$bilty->bilty_number.' advance', $companyId, [
            $this->line($receivedIn->id, $advance, 0, 'Advance received'),
            $this->line($this->partyLedger($bilty->consignor_id, $companyId)->id, 0, $advance, 'Advance from consignor'),
        ]);
    }

    private function mapBiltyCartageCash(Bilty $bilty): ?array
    {
        $amount = $this->paise($bilty->cartage);
        if ($amount <= 0 || strcasecmp((string) $bilty->cartage_type, 'Cash') !== 0) {
            return null;
        }

        $companyId = (int) $bilty->company_id;

        return $this->finalize(AccountingVoucherType::Payment, 'Bilty '.$bilty->bilty_number.' cartage cash', $companyId, [
            $this->line($this->system($companyId, 'cartage_expense'), $amount, 0, 'Cartage expense'),
            $this->line($this->system($companyId, 'cash'), 0, $amount, 'Cartage paid'),
        ]);
    }

    private function mapBiltyCartageDue(Bilty $bilty): ?array
    {
        $amount = $this->paise($bilty->cartage);
        if ($amount <= 0 || strcasecmp((string) $bilty->cartage_type, 'Due') !== 0 || ! $bilty->cartage_account_id) {
            return null;
        }

        $companyId = (int) $bilty->company_id;
        $creditor = Account::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereKey($bilty->cartage_account_id)
            ->first();

        if (! $creditor || ! $creditor->is_active) {
            return null;
        }

        return $this->finalize(AccountingVoucherType::Purchase, 'Bilty '.$bilty->bilty_number.' cartage due', $companyId, [
            $this->line($this->system($companyId, 'cartage_expense'), $amount, 0, 'Cartage expense'),
            $this->line($creditor->id, 0, $amount, 'Cartage payable'),
        ]);
    }

    /**
     * Dr Cash/Bank/party for the amount collected and Discount for `less`;
     * Cr freight, delivery charges, demurrage and GST output.
     */
    private function mapDeliveryCollection(DeliveryReceipt $receipt): ?array
    {
        $companyId = (int) $receipt->company_id;
        $payerId = $receipt->payment_due_from_id ?: $receipt->consignee_id;

        $collectedIn = match ($receipt->mode_of_payment) {
            'Cash', 'Bank' => $this->cashOrBank($receipt->mode_of_payment, $receipt->bank_account_id, $companyId),
            'Due' => $this->partyLedger($receipt->payment_due_from_id ?: $receipt->consignee_id, $companyId),
            default => null,
        };

        if (! $collectedIn) {
            return null;
        }

        $freight = $this->paise($receipt->freight);
        $deliveryCharges = $this->paise($receipt->handling_charge) + $this->paise($receipt->delivery_charge)
            + $this->paise($receipt->stationery_charge) + $this->paise($receipt->carriage_charge)
            + $this->paise($receipt->other_charge);
        $demurrage = $this->paise($receipt->demurrage);
        $charges = $freight + $deliveryCharges + $demurrage;
        $final = $this->paise($receipt->final_amount);
        $less = $this->paise($receipt->less);
        $gstInTotal = $receipt->gst_pay_by === 'Transporter' ? $this->paise($receipt->gst) : 0;

        if ($charges <= 0 || $gstInTotal > $charges) {
            return null;
        }

        return $this->finalize(
            $receipt->mode_of_payment === 'Due' ? AccountingVoucherType::Journal : AccountingVoucherType::Receipt,
            'Delivery Receipt '.$receipt->delivery_receipt_no,
            $companyId,
            [
                $this->line($collectedIn->id, $final, 0, 'Delivery collection'),
                $this->line($this->system($companyId, 'discount_allowed'), $less, 0, 'Discount allowed'),
                $this->line($this->system($companyId, 'freight_income'), 0, $freight, 'Freight income'),
                $this->line($this->system($companyId, 'delivery_charges_income'), 0, $deliveryCharges, 'Delivery charges'),
                $this->line($this->system($companyId, 'demurrage_income'), 0, $demurrage, 'Demurrage'),
                ...$this->gstOutputLines($companyId, $gstInTotal, $this->branchStateId($receipt->branch_id), $this->partyStateId($payerId)),
            ],
        );
    }

    /**
     * Crossing freight owed to the forwarding agent; delivery rows settle through the delivery receipt.
     */
    private function mapInwardAgentCrossing(InwardRegister $register): ?array
    {
        $companyId = (int) $register->company_id;
        $register->loadMissing('details');

        $byAgent = [];
        foreach ($register->details as $detail) {
            $amount = $this->paise($detail->freight);
            if ($amount <= 0 || ! $detail->agent_id || $detail->disposition !== 'forward') {
                continue;
            }

            $accountId = $this->agentLedger($detail->agent_id, $companyId)->id;
            $byAgent[$accountId] = ($byAgent[$accountId] ?? 0) + $amount;
        }

        $expense = $this->system($companyId, 'agent_crossing_expense');
        $lines = [];
        foreach ($byAgent as $accountId => $amount) {
            $lines[] = $this->line($expense, $amount, 0, 'Inward crossing freight');
            $lines[] = $this->line($accountId, 0, $amount, 'Inward crossing freight');
        }

        return $this->finalize(AccountingVoucherType::Journal, 'Inward '.$register->inward_register_no, $companyId, $lines);
    }

    /**
     * Itemised settlement; the net debit to the agent equals the document's total.
     */
    private function mapAgentSettlement(Challan|MainChallan $document, string $label, float $cashPaid): ?array
    {
        if (! $document->pay_by_agent_id) {
            return null;
        }

        $companyId = (int) $document->company_id;
        $agent = $this->agentLedger($document->pay_by_agent_id, $companyId)->id;
        $cash = $this->system($companyId, 'cash');
        $collected = $this->paise($document->to_pay_amount) + $this->paise($document->crossing_freight);

        $expenseLines = [];
        foreach ([
            'labour_charge' => ['labour_expense', 'Labour'],
            'kat' => ['kat_expense', 'Kat'],
            'home_delivery_charge' => ['home_delivery_expense', 'Home delivery'],
            'truck_freight' => ['truck_hire_expense', 'Truck freight'],
        ] as $field => [$systemKey, $narration]) {
            $amount = $this->paise($document->{$field});
            $expenseLines[] = $this->line($this->system($companyId, $systemKey), $amount, 0, $narration);
            $expenseLines[] = $this->line($agent, 0, $amount, $narration);
        }

        $cashReceived = $this->paise($document->cash_received);
        $cashPaidPaise = $this->paise($cashPaid);

        return $this->finalize(AccountingVoucherType::Journal, $label.' agent settlement', $companyId, [
            $this->line($agent, $collected, 0, 'To pay and crossing freight'),
            $this->line($this->system($companyId, 'freight_income'), 0, $collected, 'To pay and crossing freight'),
            ...$expenseLines,
            $this->line($cash, $cashReceived, 0, 'Cash received from agent'),
            $this->line($agent, 0, $cashReceived, 'Cash received from agent'),
            $this->line($agent, $cashPaidPaise, 0, 'Cash paid to agent'),
            $this->line($cash, 0, $cashPaidPaise, 'Cash paid to agent'),
        ]);
    }

    /**
     * Drops zero lines, absorbs up to ₹1 of rounding into Round Off, and
     * rejects anything that still does not balance.
     *
     * @param  list<array{account_id:int,debit:int,credit:int,narration:?string}>  $lines
     */
    /**
     * Pre-accounting Payment/Receipt vouchers: cash or bank against the party/agent
     * they were recorded for, or the unlinked control ledger when there is none.
     */
    private function mapLegacyVoucher(Voucher $voucher): ?array
    {
        $type = match (strtolower((string) $voucher->voucher_type)) {
            'receipt' => AccountingVoucherType::Receipt,
            'payment' => AccountingVoucherType::Payment,
            default => null,
        };
        $amount = $this->paise($voucher->amount);
        if ($type === null || $amount <= 0) {
            return null;
        }

        $companyId = (int) $voucher->company_id;
        $money = $this->cashOrBank($voucher->mode ?: 'Cash', $voucher->bank_account_id, $companyId);
        if (! $money) {
            return null;
        }

        $isReceipt = $type === AccountingVoucherType::Receipt;
        $counterparty = match ($voucher->accountable_type) {
            Party::class => $this->partyLedger((int) $voucher->accountable_id, $companyId),
            Agent::class => $this->agentLedger((int) $voucher->accountable_id, $companyId),
            default => $this->chartOfAccounts->systemAccount($companyId, $isReceipt ? 'sundry_debtors_unlinked' : 'sundry_creditors_unlinked'),
        };

        $narration = trim('Legacy voucher '.($voucher->voucher_number ?? '#'.$voucher->id).($voucher->remark ? ': '.$voucher->remark : ''));

        return $this->finalize($type, $narration, $companyId, $isReceipt
            ? [$this->line($money->id, $amount, 0), $this->line($counterparty->id, 0, $amount)]
            : [$this->line($counterparty->id, $amount, 0), $this->line($money->id, 0, $amount)]);
    }

    private function finalize(AccountingVoucherType $type, string $narration, int $companyId, array $lines): ?array
    {
        $lines = array_values(array_filter($lines, fn (array $line) => $line['debit'] > 0 || $line['credit'] > 0));

        $difference = array_sum(array_column($lines, 'debit')) - array_sum(array_column($lines, 'credit'));
        if ($difference !== 0) {
            if (abs($difference) > self::MAX_ROUND_OFF_PAISE) {
                return null;
            }

            $lines[] = $this->line($this->system($companyId, 'round_off'), $difference < 0 ? -$difference : 0, $difference > 0 ? $difference : 0, 'Round off');
        }

        if (count($lines) < 2) {
            return null;
        }

        return [
            'voucher_type' => $type,
            'narration' => $narration,
            'lines' => array_map(fn (array $line) => [
                'account_id' => $line['account_id'],
                'debit' => VoucherService::fromPaise($line['debit']),
                'credit' => VoucherService::fromPaise($line['credit']),
                'narration' => $line['narration'],
            ], $lines),
        ];
    }

    /**
     * A negative amount moves to the opposite side.
     *
     * @return array{account_id:int,debit:int,credit:int,narration:?string}
     */
    private function line(int $accountId, int $debit, int $credit, ?string $narration = null): array
    {
        $net = $debit - $credit;

        return [
            'account_id' => $accountId,
            'debit' => max($net, 0),
            'credit' => max(-$net, 0),
            'narration' => $narration,
        ];
    }

    /**
     * @return list<array{account_id:int,debit:int,credit:int,narration:?string}>
     */
    private function gstOutputLines(int $companyId, int $gstPaise, ?int $supplierStateId, ?int $recipientStateId): array
    {
        if ($gstPaise <= 0) {
            return [];
        }

        $split = GstCalculator::split($gstPaise / 100, $supplierStateId, $recipientStateId);

        return $this->splitGstLines($companyId, [
            'cgst' => $this->paise($split['cgst']),
            'sgst' => $this->paise($split['sgst']),
            'igst' => $this->paise($split['igst']),
        ]);
    }

    /**
     * @param  array{cgst:int,sgst:int,igst:int}  $split
     * @return list<array{account_id:int,debit:int,credit:int,narration:?string}>
     */
    private function splitGstLines(int $companyId, array $split): array
    {
        return [
            $this->line($this->system($companyId, 'gst_output_cgst'), 0, $split['cgst'], 'Output CGST'),
            $this->line($this->system($companyId, 'gst_output_sgst'), 0, $split['sgst'], 'Output SGST'),
            $this->line($this->system($companyId, 'gst_output_igst'), 0, $split['igst'], 'Output IGST'),
        ];
    }

    private function cashOrBank(?string $mode, ?int $bankAccountId, int $companyId): ?Account
    {
        if (strcasecmp((string) $mode, 'Cash') === 0) {
            return $this->chartOfAccounts->systemAccount($companyId, 'cash');
        }

        if (strcasecmp((string) $mode, 'Bank') !== 0 || ! $bankAccountId) {
            return null;
        }

        $ledger = $this->linker->bankLedger($bankAccountId, $companyId);
        if (! $ledger) {
            $bank = BankAccount::query()->withoutGlobalScopes()->where('company_id', $companyId)->find($bankAccountId);
            $ledger = $bank ? $this->linker->syncBankAccount($bank) : null;
        }

        return $ledger?->is_active ? $ledger : null;
    }

    /**
     * Parties not flagged for accounting post to the unlinked debtors control ledger.
     */
    private function partyLedger(?int $partyId, int $companyId): Account
    {
        $party = $partyId ? Party::query()->withoutGlobalScopes()->where('company_id', $companyId)->find($partyId) : null;

        return $this->entityLedger($party, $companyId, 'sundry_debtors_unlinked');
    }

    /**
     * Agents not flagged for accounting post to the unlinked creditors control ledger.
     */
    private function agentLedger(?int $agentId, int $companyId): Account
    {
        $agent = $agentId ? Agent::query()->withoutGlobalScopes()->where('company_id', $companyId)->find($agentId) : null;

        return $this->entityLedger($agent, $companyId, 'sundry_creditors_unlinked');
    }

    private function entityLedger(?Model $entity, int $companyId, string $fallbackKey): Account
    {
        if ($entity && $this->linker->isAccountingEnabled($entity)) {
            $account = $this->linker->linkedAccount($entity);
            if ($account?->is_active) {
                return $account;
            }
        }

        return $this->chartOfAccounts->systemAccount($companyId, $fallbackKey);
    }

    private function system(int $companyId, string $systemKey): int
    {
        return $this->chartOfAccounts->systemAccount($companyId, $systemKey)->id;
    }

    private function branchStateId(?int $branchId): ?int
    {
        $stateId = $branchId ? Branch::query()->withoutGlobalScopes()->whereKey($branchId)->value('state_id') : null;

        return $stateId ? (int) $stateId : null;
    }

    private function partyStateId(?int $partyId): ?int
    {
        $stateId = $partyId ? Party::query()->withoutGlobalScopes()->whereKey($partyId)->value('state_id') : null;

        return $stateId ? (int) $stateId : null;
    }

    private function paise(float|int|string|null $amount): int
    {
        return VoucherService::toPaise($amount);
    }
}
