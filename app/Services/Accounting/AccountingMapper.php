<?php

namespace App\Services\Accounting;

use App\Enums\AccountingVoucherType;
use App\Models\Account;
use App\Models\AccountingMapping;
use App\Models\Agent;
use App\Models\Bilty;
use App\Models\Challan;
use App\Models\Invoice;
use App\Models\InwardRegister;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AccountingMapper
{
    public function __construct(
        private ChartOfAccountsService $chartOfAccounts,
        private EntityAccountLinker $linker,
    ) {}

    /**
     * @return array{voucher_type: AccountingVoucherType, narration: ?string, lines: list<array{account_id:int,debit:float,credit:float,narration?:?string}>}|null
     */
    public function map(string $eventKey, Model $document): ?array
    {
        $companyId = (int) $document->company_id;
        $this->chartOfAccounts->ensureDefaults($companyId);

        return match ($eventKey) {
            'invoice.freight' => $this->mapInvoiceFreight($document),
            'bilty.cartage_due' => $this->mapBiltyCartageDue($document),
            'inward.agent_crossing' => $this->mapInwardAgentCrossing($document),
            'challan.agent_settlement' => $this->mapChallanAgentSettlement($document),
            default => $this->mapFromStoredRules($eventKey, $document),
        };
    }

    private function mapInvoiceFreight(Model $document): ?array
    {
        if (! $document instanceof Invoice) {
            return null;
        }

        $amount = round((float) ($document->net_amount ?? 0), 2);
        if ($amount <= 0 || ! $document->party_id) {
            return null;
        }

        $document->loadMissing('party');
        $party = $document->party;
        if (! $party || ! $this->linker->isAccountingEnabled($party)) {
            return null;
        }

        $partyAccount = $this->linker->linkedAccount($party);
        if (! $partyAccount || ! $partyAccount->is_selectable) {
            return null;
        }

        $income = $this->chartOfAccounts->systemAccount($document->company_id, 'freight_income');

        return [
            'voucher_type' => AccountingVoucherType::Sales,
            'narration' => 'Invoice '.$document->invoice_no,
            'lines' => [
                ['account_id' => $partyAccount->id, 'debit' => $amount, 'credit' => 0, 'narration' => 'Invoice receivable'],
                ['account_id' => $income->id, 'debit' => 0, 'credit' => $amount, 'narration' => 'Freight income'],
            ],
        ];
    }

    private function mapBiltyCartageDue(Model $document): ?array
    {
        if (! $document instanceof Bilty) {
            return null;
        }

        if (strcasecmp((string) $document->cartage_type, 'Due') !== 0) {
            return null;
        }

        $amount = round((float) ($document->cartage ?? 0), 2);
        $accountId = (int) ($document->cartage_account_id ?? 0);

        if ($amount <= 0 || $accountId <= 0) {
            return null;
        }

        $creditor = Account::query()
            ->where('company_id', $document->company_id)
            ->whereKey($accountId)
            ->first();

        if (! $creditor || ! $creditor->is_active) {
            return null;
        }

        $expense = $this->chartOfAccounts->systemAccount($document->company_id, 'cartage_expense');

        return [
            'voucher_type' => AccountingVoucherType::Purchase,
            'narration' => 'Bilty '.$document->bilty_number.' cartage due',
            'lines' => [
                ['account_id' => $expense->id, 'debit' => $amount, 'credit' => 0, 'narration' => 'Cartage expense'],
                ['account_id' => $creditor->id, 'debit' => 0, 'credit' => $amount, 'narration' => 'Cartage payable'],
            ],
        ];
    }

    private function mapInwardAgentCrossing(Model $document): ?array
    {
        if (! $document instanceof InwardRegister) {
            return null;
        }

        $document->loadMissing('details.agent');
        $expense = $this->chartOfAccounts->systemAccount($document->company_id, 'agent_crossing_expense');

        /** @var Collection<int, array{account_id:int,debit:float,credit:float,narration:?string}> $lines */
        $lines = collect();

        $byAgent = [];
        foreach ($document->details as $detail) {
            $amount = round((float) ($detail->freight ?? 0), 2);
            if ($amount <= 0 || ! $detail->agent) {
                continue;
            }

            $agent = $detail->agent;
            if (! $this->linker->isAccountingEnabled($agent)) {
                continue;
            }

            $agentAccount = $this->linker->linkedAccount($agent);
            if (! $agentAccount || ! $agentAccount->is_selectable) {
                continue;
            }

            $byAgent[$agent->id] ??= ['account_id' => $agentAccount->id, 'amount' => 0.0];
            $byAgent[$agent->id]['amount'] += $amount;
        }

        foreach ($byAgent as $row) {
            $amount = round($row['amount'], 2);
            if ($amount <= 0) {
                continue;
            }

            $lines->push([
                'account_id' => $expense->id,
                'debit' => $amount,
                'credit' => 0,
                'narration' => 'Inward crossing freight',
            ]);
            $lines->push([
                'account_id' => $row['account_id'],
                'debit' => 0,
                'credit' => $amount,
                'narration' => 'Inward crossing freight',
            ]);
        }

        if ($lines->isEmpty()) {
            return null;
        }

        return [
            'voucher_type' => AccountingVoucherType::Journal,
            'narration' => 'Inward '.$document->inward_register_no,
            'lines' => $lines->values()->all(),
        ];
    }

    private function mapChallanAgentSettlement(Model $document): ?array
    {
        if (! $document instanceof Challan) {
            return null;
        }

        $amount = round((float) ($document->total_amount ?? 0), 2);
        if ($amount == 0.0 || ! $document->pay_by_agent_id) {
            return null;
        }

        $document->loadMissing('payByAgent');
        $agent = $document->payByAgent;
        if (! $agent instanceof Agent || ! $this->linker->isAccountingEnabled($agent)) {
            return null;
        }

        $agentAccount = $this->linker->linkedAccount($agent);
        if (! $agentAccount || ! $agentAccount->is_selectable) {
            return null;
        }

        $abs = abs($amount);

        if ($amount > 0) {
            $expense = $this->chartOfAccounts->systemAccount($document->company_id, 'agent_challan_expense');

            return [
                'voucher_type' => AccountingVoucherType::Journal,
                'narration' => 'Challan '.$document->challan_number.' agent settlement',
                'lines' => [
                    ['account_id' => $expense->id, 'debit' => $abs, 'credit' => 0],
                    ['account_id' => $agentAccount->id, 'debit' => 0, 'credit' => $abs],
                ],
            ];
        }

        $income = $this->chartOfAccounts->systemAccount($document->company_id, 'agent_challan_income');

        return [
            'voucher_type' => AccountingVoucherType::Journal,
            'narration' => 'Challan '.$document->challan_number.' agent settlement',
            'lines' => [
                ['account_id' => $agentAccount->id, 'debit' => $abs, 'credit' => 0],
                ['account_id' => $income->id, 'debit' => 0, 'credit' => $abs],
            ],
        ];
    }

    private function mapFromStoredRules(string $eventKey, Model $document): ?array
    {
        AccountingMapping::query()
            ->where('event_key', $eventKey)
            ->where('is_active', true)
            ->where(function ($query) use ($document) {
                $query->whereNull('company_id')
                    ->orWhere('company_id', $document->company_id);
            })
            ->orderByRaw('company_id is null')
            ->first();

        return null;
    }
}
