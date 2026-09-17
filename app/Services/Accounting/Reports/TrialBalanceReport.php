<?php

namespace App\Services\Accounting\Reports;

use App\Enums\AccountingVoucherStatus;
use App\Models\Account;
use App\Models\AccountingVoucherEntry;
use Illuminate\Support\Carbon;

class TrialBalanceReport
{
    /**
     * @return array{rows: list<array<string, mixed>>, totals: array{debit: float, credit: float}}
     */
    public function build(int $companyId, Carbon|string $from, Carbon|string $to): array
    {
        $fromDate = Carbon::parse($from)->toDateString();
        $toDate = Carbon::parse($to)->toDateString();

        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->with('accountGroup:id,name,nature,parent_id')
            ->orderBy('name')
            ->get();

        $rows = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($accounts as $account) {
            $openingDebit = (float) $this->sumBefore($account->id, $fromDate, 'debit');
            $openingCredit = (float) $this->sumBefore($account->id, $fromDate, 'credit');
            $periodDebit = (float) $this->sumBetween($account->id, $fromDate, $toDate, 'debit');
            $periodCredit = (float) $this->sumBetween($account->id, $fromDate, $toDate, 'credit');

            $opening = round($openingDebit - $openingCredit, 2);
            $closing = round($opening + $periodDebit - $periodCredit, 2);

            if ($opening == 0.0 && $periodDebit == 0.0 && $periodCredit == 0.0) {
                continue;
            }

            $closingDebit = $closing > 0 ? $closing : 0.0;
            $closingCredit = $closing < 0 ? abs($closing) : 0.0;

            $rows[] = [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'group_id' => $account->account_group_id,
                'group_name' => $account->accountGroup?->name,
                'nature' => $account->accountGroup?->nature?->value,
                'opening' => $opening,
                'debit' => round($periodDebit, 2),
                'credit' => round($periodCredit, 2),
                'closing_debit' => $closingDebit,
                'closing_credit' => $closingCredit,
            ];

            $totalDebit += $closingDebit;
            $totalCredit += $closingCredit;
        }

        return [
            'rows' => $rows,
            'totals' => [
                'debit' => round($totalDebit, 2),
                'credit' => round($totalCredit, 2),
            ],
        ];
    }

    private function sumBefore(int $accountId, string $beforeDate, string $column): float
    {
        return (float) AccountingVoucherEntry::query()
            ->where('account_id', $accountId)
            ->whereHas('voucher', fn ($q) => $q->where('status', AccountingVoucherStatus::Posted)->whereDate('date', '<', $beforeDate))
            ->sum($column);
    }

    private function sumBetween(int $accountId, string $from, string $to, string $column): float
    {
        return (float) AccountingVoucherEntry::query()
            ->where('account_id', $accountId)
            ->whereHas('voucher', fn ($q) => $q
                ->where('status', AccountingVoucherStatus::Posted)
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to))
            ->sum($column);
    }
}
