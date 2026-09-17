<?php

namespace App\Services\Accounting\Reports;

use App\Enums\AccountingVoucherStatus;
use App\Enums\AccountNature;
use App\Models\Account;
use App\Models\AccountingVoucherEntry;
use Illuminate\Support\Carbon;

class ProfitAndLossReport
{
    /**
     * @return array{income: list<array<string,mixed>>, expenses: list<array<string,mixed>>, net: float}
     */
    public function build(int $companyId, Carbon|string $from, Carbon|string $to): array
    {
        $fromDate = Carbon::parse($from)->toDateString();
        $toDate = Carbon::parse($to)->toDateString();

        $income = [];
        $expenses = [];
        $incomeTotal = 0.0;
        $expenseTotal = 0.0;

        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->whereHas('accountGroup', fn ($q) => $q->whereIn('nature', [AccountNature::Income->value, AccountNature::Expenses->value]))
            ->with('accountGroup:id,name,nature')
            ->orderBy('name')
            ->get();

        foreach ($accounts as $account) {
            $debit = (float) AccountingVoucherEntry::query()
                ->where('account_id', $account->id)
                ->whereHas('voucher', fn ($q) => $q->where('status', AccountingVoucherStatus::Posted)->whereDate('date', '>=', $fromDate)->whereDate('date', '<=', $toDate))
                ->sum('debit');
            $credit = (float) AccountingVoucherEntry::query()
                ->where('account_id', $account->id)
                ->whereHas('voucher', fn ($q) => $q->where('status', AccountingVoucherStatus::Posted)->whereDate('date', '>=', $fromDate)->whereDate('date', '<=', $toDate))
                ->sum('credit');

            $nature = $account->accountGroup?->nature;
            if ($nature === AccountNature::Income) {
                $amount = round($credit - $debit, 2);
                if ($amount == 0.0) {
                    continue;
                }
                $income[] = [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'group_name' => $account->accountGroup?->name,
                    'amount' => $amount,
                ];
                $incomeTotal += $amount;
            } elseif ($nature === AccountNature::Expenses) {
                $amount = round($debit - $credit, 2);
                if ($amount == 0.0) {
                    continue;
                }
                $expenses[] = [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'group_name' => $account->accountGroup?->name,
                    'amount' => $amount,
                ];
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
