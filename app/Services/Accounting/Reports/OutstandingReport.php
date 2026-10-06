<?php

namespace App\Services\Accounting\Reports;

use Illuminate\Support\Carbon;

class OutstandingReport
{
    public function __construct(private AccountTotals $totals) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function build(int $companyId, string $groupSystemKey, Carbon|string $from, Carbon|string $to, ?int $branchId = null): array
    {
        $groupIds = $this->totals->groupTreeIds($companyId, $groupSystemKey);
        if ($groupIds === []) {
            return [];
        }

        $accounts = $this->totals->accounts($companyId)->whereIn('account_group_id', $groupIds);
        if ($accounts->isEmpty()) {
            return [];
        }

        $sums = $this->totals->forPeriod($companyId, $from, $to, $branchId, $accounts->keys()->all());

        $rows = [];
        foreach ($accounts as $account) {
            $sum = $sums->get($account->id);
            if ($sum === null) {
                continue;
            }

            $opening = round($sum['opening_debit'] - $sum['opening_credit'], 2);
            if ($opening == 0.0 && $sum['debit'] == 0.0 && $sum['credit'] == 0.0) {
                continue;
            }

            $rows[] = [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'opening' => $opening,
                'debit' => $sum['debit'],
                'credit' => $sum['credit'],
                'outstanding' => round($opening + $sum['debit'] - $sum['credit'], 2),
            ];
        }

        return $rows;
    }
}
