<?php

namespace App\Services\Accounting\Reports;

use App\Enums\AccountNature;
use Illuminate\Support\Carbon;

class ExpenseReport
{
    public function __construct(private AccountTotals $totals) {}

    /**
     * Expense ledgers grouped by account group, with month-wise amounts (debit minus credit).
     *
     * @return array{months: list<string>, groups: list<array{group_name: string|null, total: float, ledgers: list<array{account_id: int, account_name: string, total: float, months: array<string, float>}>}>, total: float}
     */
    public function build(int $companyId, Carbon|string $from, Carbon|string $to, ?int $branchId = null): array
    {
        $fromDate = Carbon::parse($from)->toDateString();
        $toDate = Carbon::parse($to)->toDateString();

        $accounts = $this->totals->accounts($companyId)
            ->filter(fn ($account) => $account->accountGroup?->nature === AccountNature::Expenses);

        if ($accounts->isEmpty()) {
            return ['months' => [], 'groups' => [], 'total' => 0.0];
        }

        $daily = $this->totals->postedEntries($companyId, $branchId)
            ->whereIn('e.account_id', $accounts->keys()->all())
            ->whereDate('v.date', '>=', $fromDate)
            ->whereDate('v.date', '<=', $toDate)
            ->groupBy('e.account_id', 'v.date')
            ->selectRaw('e.account_id, v.date AS voucher_date, SUM(e.debit) AS debit, SUM(e.credit) AS credit')
            ->get();

        $ledgers = [];
        $months = [];
        foreach ($daily as $row) {
            $month = Carbon::parse($row->voucher_date)->format('Y-m');
            $amount = round((float) $row->debit - (float) $row->credit, 2);
            $accountId = (int) $row->account_id;

            $months[$month] = true;
            $ledgers[$accountId][$month] = round(($ledgers[$accountId][$month] ?? 0) + $amount, 2);
        }

        $groups = [];
        $grandTotal = 0.0;
        foreach ($accounts as $account) {
            if (! isset($ledgers[$account->id])) {
                continue;
            }

            $total = round(array_sum($ledgers[$account->id]), 2);
            if ($total == 0.0) {
                continue;
            }

            $groupName = $account->accountGroup?->name;
            $groups[$groupName] ??= ['group_name' => $groupName, 'total' => 0.0, 'ledgers' => []];
            $groups[$groupName]['ledgers'][] = [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'total' => $total,
                'months' => $ledgers[$account->id],
            ];
            $groups[$groupName]['total'] = round($groups[$groupName]['total'] + $total, 2);
            $grandTotal += $total;
        }

        $monthKeys = array_keys($months);
        sort($monthKeys);
        ksort($groups);

        return [
            'months' => $monthKeys,
            'groups' => array_values($groups),
            'total' => round($grandTotal, 2),
        ];
    }
}
