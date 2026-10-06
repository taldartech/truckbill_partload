<?php

namespace App\Services\Accounting\Reports;

use App\Enums\AccountNature;
use Illuminate\Support\Carbon;

class ProfitAndLossReport
{
    public function __construct(private AccountTotals $totals) {}

    /**
     * @return array{income: list<array<string,mixed>>, expenses: list<array<string,mixed>>, income_total: float, expense_total: float, net: float}
     */
    public function build(int $companyId, Carbon|string $from, Carbon|string $to, ?int $branchId = null): array
    {
        $accounts = $this->totals->accounts($companyId);
        $sums = $this->totals->forPeriod($companyId, $from, $to, $branchId);

        $income = [];
        $expenses = [];
        $incomeTotal = 0.0;
        $expenseTotal = 0.0;

        foreach ($accounts as $account) {
            $sum = $sums->get($account->id);
            $nature = $account->accountGroup?->nature;
            if ($sum === null || ! in_array($nature, [AccountNature::Income, AccountNature::Expenses], true)) {
                continue;
            }

            $amount = $nature === AccountNature::Income
                ? round($sum['credit'] - $sum['debit'], 2)
                : round($sum['debit'] - $sum['credit'], 2);

            if ($amount == 0.0) {
                continue;
            }

            $row = [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'group_name' => $account->accountGroup?->name,
                'amount' => $amount,
            ];

            if ($nature === AccountNature::Income) {
                $income[] = $row;
                $incomeTotal += $amount;
            } else {
                $expenses[] = $row;
                $expenseTotal += $amount;
            }
        }

        return [
            'income' => $income,
            'expenses' => $expenses,
            'income_total' => round($incomeTotal, 2),
            'expense_total' => round($expenseTotal, 2),
            'net' => round($incomeTotal - $expenseTotal, 2),
        ];
    }
}
