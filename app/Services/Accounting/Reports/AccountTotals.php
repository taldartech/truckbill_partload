<?php

namespace App\Services\Accounting\Reports;

use App\Enums\AccountingVoucherStatus;
use App\Models\Account;
use App\Models\AccountGroup;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates posted voucher entries per account in a single grouped query.
 */
class AccountTotals
{
    /**
     * Opening (before $from) and period ($from..$to) sums per account. A null $from puts everything up to $to in the period.
     *
     * @param  list<int>|null  $accountIds
     * @return Collection<int, array{opening_debit: float, opening_credit: float, debit: float, credit: float}>
     */
    public function forPeriod(int $companyId, Carbon|string|null $from, Carbon|string $to, ?int $branchId = null, ?array $accountIds = null): Collection
    {
        $fromDate = $from !== null ? Carbon::parse($from)->toDateString() : '0001-01-01';
        $toDate = Carbon::parse($to)->toDateString();

        return $this->postedEntries($companyId, $branchId)
            ->whereDate('v.date', '<=', $toDate)
            ->when($accountIds !== null, fn (Builder $query) => $query->whereIn('e.account_id', $accountIds))
            ->groupBy('e.account_id')
            ->selectRaw('e.account_id')
            ->selectRaw('SUM(CASE WHEN DATE(v.date) < ? THEN e.debit ELSE 0 END) AS opening_debit', [$fromDate])
            ->selectRaw('SUM(CASE WHEN DATE(v.date) < ? THEN e.credit ELSE 0 END) AS opening_credit', [$fromDate])
            ->selectRaw('SUM(CASE WHEN DATE(v.date) >= ? THEN e.debit ELSE 0 END) AS period_debit', [$fromDate])
            ->selectRaw('SUM(CASE WHEN DATE(v.date) >= ? THEN e.credit ELSE 0 END) AS period_credit', [$fromDate])
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->account_id => [
                'opening_debit' => round((float) $row->opening_debit, 2),
                'opening_credit' => round((float) $row->opening_credit, 2),
                'debit' => round((float) $row->period_debit, 2),
                'credit' => round((float) $row->period_credit, 2),
            ]]);
    }

    /**
     * Posted entries joined to their vouchers, filtered by company and optionally branch.
     */
    public function postedEntries(int $companyId, ?int $branchId = null): Builder
    {
        return DB::table('accounting_voucher_entries as e')
            ->join('accounting_vouchers as v', 'v.id', '=', 'e.voucher_id')
            ->where('v.company_id', $companyId)
            ->where('v.status', AccountingVoucherStatus::Posted->value)
            ->when($branchId !== null, fn (Builder $query) => $query->where('v.branch_id', $branchId));
    }

    /**
     * Company accounts (with group) keyed by id, without tenant global scopes.
     *
     * @return Collection<int, Account>
     */
    public function accounts(int $companyId): Collection
    {
        return Account::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['accountGroup' => fn ($query) => $query->withoutGlobalScopes()->select('id', 'name', 'nature', 'parent_id', 'system_key')])
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    /**
     * The group with the given system key plus all of its descendants.
     *
     * @return list<int>
     */
    public function groupTreeIds(int $companyId, string $groupSystemKey): array
    {
        $groups = AccountGroup::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->get(['id', 'parent_id', 'system_key']);

        $root = $groups->firstWhere('system_key', $groupSystemKey);
        if (! $root) {
            return [];
        }

        $ids = [$root->id];
        $frontier = [$root->id];
        while ($frontier !== []) {
            $frontier = $groups->whereIn('parent_id', $frontier)->pluck('id')->diff($ids)->values()->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }
}
