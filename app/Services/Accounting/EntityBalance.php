<?php

namespace App\Services\Accounting;

use App\Models\Agent;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\Party;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;

class EntityBalance
{
    public function __construct(private LedgerAccountResolver $resolver) {}

    /**
     * Outstanding amount in the entity's natural direction.
     * Party: receivable (debit balance). Vendor/Agent: payable (credit balance).
     */
    public function outstanding(Model $entity): float
    {
        $account = $this->resolver->forEntity($entity);

        $totals = LedgerEntry::query()
            ->where('ledger_account_id', $account->id)
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        $debit = (float) ($totals->total_debit ?? 0);
        $credit = (float) ($totals->total_credit ?? 0);

        return match ($entity::class) {
            Party::class => round($debit - $credit, 2),
            Vendor::class, Agent::class => round($credit - $debit, 2),
            default => round($debit - $credit, 2),
        };
    }

    public function exceedsOutstanding(Model $entity, float $amount, string $voucherType): bool
    {
        $outstanding = $this->outstanding($entity);

        if ($outstanding <= 0) {
            return $amount > 0;
        }

        return $amount > $outstanding;
    }

    public function balanceForAccount(LedgerAccount $account): float
    {
        $totals = LedgerEntry::query()
            ->where('ledger_account_id', $account->id)
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        return round(((float) ($totals->total_debit ?? 0)) - ((float) ($totals->total_credit ?? 0)), 2);
    }
}
