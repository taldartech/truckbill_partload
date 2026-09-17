<?php

namespace App\Services\Accounting\Reports;

use App\Enums\AccountingVoucherStatus;
use App\Enums\AccountNature;
use App\Models\Account;
use App\Models\AccountingVoucherEntry;
use Illuminate\Support\Carbon;

class BalanceSheetReport
{
    /**
     * @return array{assets: list<array<string,mixed>>, liabilities: list<array<string,mixed>>, capital: list<array<string,mixed>>, totals: array{assets: float, liabilities_capital: float}, net_profit: float}
     */
    public function build(int $companyId, Carbon|string $asOn, ProfitAndLossReport $pnl): array
    {
        $asOnDate = Carbon::parse($asOn)->toDateString();
        $fyStart = Carbon::parse($asOn)->month >= 4
            ? Carbon::create(Carbon::parse($asOn)->year, 4, 1)
            : Carbon::create(Carbon::parse($asOn)->year - 1, 4, 1);

        $pnlData = $pnl->build($companyId, $fyStart, $asOnDate);
        $netProfit = $pnlData['net'];

        $buckets = [
            AccountNature::Assets->value => [],
            AccountNature::Liabilities->value => [],
            AccountNature::Capital->value => [],
        ];
        $totals = [
            AccountNature::Assets->value => 0.0,
            AccountNature::Liabilities->value => 0.0,
            AccountNature::Capital->value => 0.0,
        ];

        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->whereHas('accountGroup', fn ($q) => $q->whereIn('nature', [
                AccountNature::Assets->value,
                AccountNature::Liabilities->value,
                AccountNature::Capital->value,
            ]))
            ->with('accountGroup:id,name,nature')
            ->orderBy('name')
            ->get();

        foreach ($accounts as $account) {
            $debit = (float) AccountingVoucherEntry::query()
                ->where('account_id', $account->id)
                ->whereHas('voucher', fn ($q) => $q->where('status', AccountingVoucherStatus::Posted)->whereDate('date', '<=', $asOnDate))
                ->sum('debit');
            $credit = (float) AccountingVoucherEntry::query()
                ->where('account_id', $account->id)
                ->whereHas('voucher', fn ($q) => $q->where('status', AccountingVoucherStatus::Posted)->whereDate('date', '<=', $asOnDate))
                ->sum('credit');

            $nature = $account->accountGroup?->nature;
            if (! $nature) {
                continue;
            }

            $raw = round($debit - $credit, 2);
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
}
