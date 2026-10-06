<?php

namespace App\Services\Accounting\Reports;

use App\Enums\AccountNature;
use Illuminate\Support\Carbon;

class BalanceSheetReport
{
    public function __construct(private AccountTotals $totals) {}

    /**
     * @return array{assets: list<array<string,mixed>>, liabilities: list<array<string,mixed>>, capital: list<array<string,mixed>>, totals: array{assets: float, liabilities_capital: float}, net_profit: float}
     */
    public function build(int $companyId, Carbon|string $asOn, ProfitAndLossReport $pnl, ?int $branchId = null): array
    {
        $asOnDate = Carbon::parse($asOn)->toDateString();
        $fyStart = Carbon::parse($asOn)->month >= 4
            ? Carbon::create(Carbon::parse($asOn)->year, 4, 1)
            : Carbon::create(Carbon::parse($asOn)->year - 1, 4, 1);

        $netProfit = $pnl->build($companyId, $fyStart, $asOnDate, $branchId)['net'];
        $retainedEarnings = $this->retainedEarnings($companyId, $fyStart, $branchId);

        $buckets = [
            AccountNature::Assets->value => [],
            AccountNature::Liabilities->value => [],
            AccountNature::Capital->value => [],
        ];
        $totals = array_fill_keys(array_keys($buckets), 0.0);

        $accounts = $this->totals->accounts($companyId);
        $sums = $this->totals->forPeriod($companyId, null, $asOnDate, $branchId);

        foreach ($accounts as $account) {
            $sum = $sums->get($account->id);
            $nature = $account->accountGroup?->nature;
            if ($sum === null || $nature === null || ! array_key_exists($nature->value, $buckets)) {
                continue;
            }

            $raw = round($sum['debit'] - $sum['credit'], 2);
            $amount = $nature->isDebitNormal() ? $raw : round(-$raw, 2);

            if ($amount == 0.0) {
                continue;
            }

            $buckets[$nature->value][] = [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'group_name' => $account->accountGroup?->name,
                'amount' => $amount,
            ];
            $totals[$nature->value] += $amount;
        }

        if ($retainedEarnings != 0.0) {
            $buckets[AccountNature::Capital->value][] = [
                'account_id' => null,
                'account_name' => 'Profit & Loss (previous years)',
                'group_name' => null,
                'amount' => $retainedEarnings,
            ];
            $totals[AccountNature::Capital->value] += $retainedEarnings;
        }

        $totals[AccountNature::Capital->value] = round($totals[AccountNature::Capital->value] + $netProfit, 2);

        return [
            'assets' => $buckets[AccountNature::Assets->value],
            'liabilities' => $buckets[AccountNature::Liabilities->value],
            'capital' => $buckets[AccountNature::Capital->value],
            'net_profit' => $netProfit,
            'totals' => [
                'assets' => round($totals[AccountNature::Assets->value], 2),
                'liabilities_capital' => round(
                    $totals[AccountNature::Liabilities->value] + $totals[AccountNature::Capital->value],
                    2
                ),
            ],
        ];
    }

    /**
     * Income minus expenses posted before the current financial year; without it the sheet would not balance after year one.
     */
    private function retainedEarnings(int $companyId, Carbon $fyStart, ?int $branchId): float
    {
        $accounts = $this->totals->accounts($companyId);
        $sums = $this->totals->forPeriod($companyId, null, $fyStart->copy()->subDay(), $branchId);

        $net = 0.0;
        foreach ($sums as $accountId => $sum) {
            $nature = $accounts->get($accountId)?->accountGroup?->nature;
            if ($nature === AccountNature::Income || $nature === AccountNature::Expenses) {
                $net += $sum['credit'] - $sum['debit'];
            }
        }

        return round($net, 2);
    }
}
