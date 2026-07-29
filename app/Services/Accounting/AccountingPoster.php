<?php

namespace App\Services\Accounting;

use App\Models\Bilty;
use App\Models\Challan;
use App\Models\Invoice;
use App\Models\InwardRegister;
use App\Models\LedgerEntry;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountingPoster
{
    public function __construct(private LedgerAccountResolver $resolver) {}

    public function syncReference(Model $document): void
    {
        match ($document::class) {
            Voucher::class => $this->syncVoucher($document),
            Bilty::class => $this->syncBilty($document),
            Invoice::class => $this->syncInvoice($document),
            InwardRegister::class => $this->syncInwardRegister($document),
            Challan::class => $this->syncChallan($document),
            default => throw new \InvalidArgumentException('Unsupported accounting reference: '.$document::class),
        };
    }

    public function clearReference(Model $document): void
    {
        LedgerEntry::query()
            ->where('reference_type', $document::class)
            ->where('reference_id', $document->getKey())
            ->delete();
    }

    public function syncVoucher(Voucher $voucher): void
    {
        $this->clearReference($voucher);

        $amount = round((float) $voucher->amount, 2);
        if ($amount <= 0 || ! $voucher->accountable) {
            return;
        }

        $entityAccount = $this->resolver->forEntity($voucher->accountable);
        $moneyAccount = $voucher->mode === 'bank' && $voucher->bankAccount
            ? $this->resolver->forBankAccount($voucher->bankAccount)
            : $this->resolver->cash($voucher->company_id);

        $group = (string) Str::uuid();
        $isReceipt = strcasecmp((string) $voucher->voucher_type, 'Receipt') === 0;

        if ($isReceipt) {
            $this->insertPair(
                $voucher,
                $group,
                'Receipt',
                $voucher->voucher_number,
                $voucher->date,
                $moneyAccount->id,
                $entityAccount->id,
                $amount,
                $voucher->remark,
            );
        } else {
            $this->insertPair(
                $voucher,
                $group,
                'Payment',
                $voucher->voucher_number,
                $voucher->date,
                $entityAccount->id,
                $moneyAccount->id,
                $amount,
                $voucher->remark,
            );
        }
    }

    public function syncBilty(Bilty $bilty): void
    {
        $this->clearReference($bilty);

        if (strcasecmp((string) $bilty->cartage_type, 'Due') !== 0) {
            return;
        }

        $amount = round((float) ($bilty->cartage ?? 0), 2);
        if ($amount <= 0 || ! $bilty->vendor_id) {
            return;
        }

        $bilty->loadMissing('vendor');
        if (! $bilty->vendor) {
            return;
        }

        $expense = $this->resolver->system($bilty->company_id, 'cartage_expense');
        $vendorAccount = $this->resolver->forEntity($bilty->vendor);
        $group = (string) Str::uuid();

        $this->insertPair(
            $bilty,
            $group,
            'Bilty',
            $bilty->bilty_number,
            $bilty->date ?? now()->toDateString(),
            $expense->id,
            $vendorAccount->id,
            $amount,
            'Cartage due',
        );
    }

    public function syncInvoice(Invoice $invoice): void
    {
        $this->clearReference($invoice);

        $amount = round((float) ($invoice->net_amount ?? 0), 2);
        if ($amount <= 0 || ! $invoice->party_id) {
            return;
        }

        $invoice->loadMissing('party');
        if (! $invoice->party) {
            return;
        }

        $partyAccount = $this->resolver->forEntity($invoice->party);
        $income = $this->resolver->system($invoice->company_id, 'freight_income');
        $group = (string) Str::uuid();

        $this->insertPair(
            $invoice,
            $group,
            'Invoice',
            $invoice->invoice_no,
            $invoice->date ?? now()->toDateString(),
            $partyAccount->id,
            $income->id,
            $amount,
            'Invoice receivable',
        );
    }

    public function syncInwardRegister(InwardRegister $inward): void
    {
        $this->clearReference($inward);

        $inward->loadMissing('details.agent');

        $byAgent = [];
        foreach ($inward->details as $detail) {
            $amount = round((float) ($detail->freight ?? 0), 2);
            if ($amount <= 0 || ! $detail->agent) {
                continue;
            }

            $agentId = $detail->agent->id;
            $byAgent[$agentId] ??= ['agent' => $detail->agent, 'amount' => 0.0];
            $byAgent[$agentId]['amount'] += $amount;
        }

        $expense = $this->resolver->system($inward->company_id, 'agent_crossing_expense');

        foreach ($byAgent as $row) {
            $amount = round($row['amount'], 2);
            if ($amount <= 0) {
                continue;
            }

            $agentAccount = $this->resolver->forEntity($row['agent']);
            $group = (string) Str::uuid();

            $this->insertPair(
                $inward,
                $group,
                'InwardRegister',
                $inward->inward_register_no,
                $inward->date ?? now()->toDateString(),
                $expense->id,
                $agentAccount->id,
                $amount,
                'Inward crossing freight',
            );
        }
    }

    public function syncChallan(Challan $challan): void
    {
        $this->clearReference($challan);

        $amount = round((float) ($challan->total_amount ?? 0), 2);
        if ($amount == 0.0 || ! $challan->pay_by_agent_id) {
            return;
        }

        $challan->loadMissing('payByAgent');
        if (! $challan->payByAgent) {
            return;
        }

        $agentAccount = $this->resolver->forEntity($challan->payByAgent);
        $abs = abs($amount);
        $group = (string) Str::uuid();

        if ($amount > 0) {
            $expense = $this->resolver->system($challan->company_id, 'agent_challan_expense');
            $this->insertPair(
                $challan,
                $group,
                'Challan',
                $challan->challan_number,
                $challan->date ?? now()->toDateString(),
                $expense->id,
                $agentAccount->id,
                $abs,
                'Challan agent settlement',
            );
        } else {
            $income = $this->resolver->system($challan->company_id, 'agent_challan_income');
            $this->insertPair(
                $challan,
                $group,
                'Challan',
                $challan->challan_number,
                $challan->date ?? now()->toDateString(),
                $agentAccount->id,
                $income->id,
                $abs,
                'Challan agent settlement',
            );
        }
    }

    private function insertPair(
        Model $reference,
        string $group,
        string $voucherType,
        ?string $voucherNumber,
        mixed $date,
        int $debitAccountId,
        int $creditAccountId,
        float $amount,
        ?string $remarks,
    ): void {
        $payload = [
            'company_id' => $reference->company_id,
            'branch_id' => $reference->branch_id ?? null,
            'date' => $date,
            'voucher_type' => $voucherType,
            'voucher_number' => $voucherNumber,
            'reference_type' => $reference::class,
            'reference_id' => $reference->getKey(),
            'remarks' => $remarks,
            'entry_group_uuid' => $group,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('ledger_entries')->insert([
            array_merge($payload, [
                'ledger_account_id' => $debitAccountId,
                'debit' => $amount,
                'credit' => 0,
            ]),
            array_merge($payload, [
                'ledger_account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $amount,
            ]),
        ]);
    }
}
