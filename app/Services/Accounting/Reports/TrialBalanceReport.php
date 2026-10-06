<?php

namespace App\Services\Accounting\Reports;

use Illuminate\Support\Carbon;

class TrialBalanceReport
{
    public function __construct(private AccountTotals $totals) {}

    /**
     * @return array{rows: list<array<string, mixed>>, groups: list<array<string, mixed>>, totals: array{debit: float, credit: float, period_debit: float, period_credit: float}}
     */
    public function build(int $companyId, Carbon|string $from, Carbon|string $to, ?int $branchId = null): array
    {
        $accounts = $this->totals->accounts($companyId);
        $sums = $this->totals->forPeriod($companyId, $from, $to, $branchId);

        $rows = [];
        $groups = [];
        $totals = ['debit' => 0.0, 'credit' => 0.0, 'period_debit' => 0.0, 'period_credit' => 0.0];

        foreach ($accounts as $account) {
            $sum = $sums->get($account->id);
            if ($sum === null) {
                continue;
            }

            $opening = round($sum['opening_debit'] - $sum['opening_credit'], 2);
            $closing = round($opening + $sum['debit'] - $sum['credit'], 2);

            if ($opening == 0.0 && $sum['debit'] == 0.0 && $sum['credit'] == 0.0) {
                continue;
            }

            $closingDebit = $closing > 0 ? $closing : 0.0;
            $closingCredit = $closing < 0 ? abs($closing) : 0.0;
            $groupId = $account->account_group_id;

            $rows[] = [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'group_id' => $groupId,
                'group_name' => $account->accountGroup?->name,
                'nature' => $account->accountGroup?->nature?->value,
                'opening' => $opening,
                'debit' => $sum['debit'],
                'credit' => $sum['credit'],
                'closing_debit' => $closingDebit,
                'closing_credit' => $closingCredit,
            ];

            $groups[$groupId] ??= [
                'group_id' => $groupId,
                'group_name' => $account->accountGroup?->name,
                'nature' => $account->accountGroup?->nature?->value,
                'debit' => 0.0,
                'credit' => 0.0,
                'closing_debit' => 0.0,
                'closing_credit' => 0.0,
            ];
            $groups[$groupId]['debit'] = round($groups[$groupId]['debit'] + $sum['debit'], 2);
            $groups[$groupId]['credit'] = round($groups[$groupId]['credit'] + $sum['credit'], 2);
            $groups[$groupId]['closing_debit'] = round($groups[$groupId]['closing_debit'] + $closingDebit, 2);
            $groups[$groupId]['closing_credit'] = round($groups[$groupId]['closing_credit'] + $closingCredit, 2);

            $totals['debit'] += $closingDebit;
            $totals['credit'] += $closingCredit;
            $totals['period_debit'] += $sum['debit'];
            $totals['period_credit'] += $sum['credit'];
        }

        usort($rows, fn (array $a, array $b): int => [$a['group_name'], $a['account_name']] <=> [$b['group_name'], $b['account_name']]);
        $groups = array_values($groups);
        usort($groups, fn (array $a, array $b): int => $a['group_name'] <=> $b['group_name']);

        return [
            'rows' => $rows,
            'groups' => $groups,
            'totals' => array_map(fn (float $value): float => round($value, 2), $totals),
        ];
    }
}
