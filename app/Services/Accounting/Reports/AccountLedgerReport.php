<?php

namespace App\Services\Accounting\Reports;

use App\Enums\AccountingVoucherStatus;
use App\Models\Account;
use App\Models\AccountingVoucherEntry;
use Illuminate\Support\Carbon;

class AccountLedgerReport
{
    /**
     * @return array{opening: float, rows: list<array<string, mixed>>, closing: float, totals: array{debit: float, credit: float}}
     */
    public function build(Account $account, Carbon|string $from, Carbon|string $to): array
    {
        $fromDate = Carbon::parse($from)->toDateString();
        $toDate = Carbon::parse($to)->toDateString();

        $openingDebit = (float) AccountingVoucherEntry::query()
            ->where('account_id', $account->id)
            ->whereHas('voucher', fn ($q) => $q->where('status', AccountingVoucherStatus::Posted)->whereDate('date', '<', $fromDate))
            ->sum('debit');

        $openingCredit = (float) AccountingVoucherEntry::query()
            ->where('account_id', $account->id)
            ->whereHas('voucher', fn ($q) => $q->where('status', AccountingVoucherStatus::Posted)->whereDate('date', '<', $fromDate))
            ->sum('credit');

        $opening = round($openingDebit - $openingCredit, 2);

        $entries = AccountingVoucherEntry::query()
            ->with(['voucher:id,date,voucher_number,voucher_type,narration,source_type,source_id,status'])
            ->where('account_id', $account->id)
            ->whereHas('voucher', fn ($q) => $q
                ->where('status', AccountingVoucherStatus::Posted)
                ->whereDate('date', '>=', $fromDate)
                ->whereDate('date', '<=', $toDate))
            ->join('accounting_vouchers', 'accounting_voucher_entries.voucher_id', '=', 'accounting_vouchers.id')
            ->orderBy('accounting_vouchers.date')
            ->orderBy('accounting_vouchers.id')
            ->orderBy('accounting_voucher_entries.line_no')
            ->select('accounting_voucher_entries.*')
            ->get();

        $running = $opening;
        $rows = [];
        $periodDebit = 0.0;
        $periodCredit = 0.0;

        foreach ($entries as $entry) {
            $debit = (float) $entry->debit;
            $credit = (float) $entry->credit;
            $running = round($running + $debit - $credit, 2);
            $periodDebit += $debit;
            $periodCredit += $credit;

            $rows[] = [
                'date' => $entry->voucher?->date?->toDateString(),
                'voucher_id' => $entry->voucher_id,
                'voucher_number' => $entry->voucher?->voucher_number,
                'voucher_type' => $entry->voucher?->voucher_type?->value,
                'particulars' => $entry->narration ?: $entry->voucher?->narration,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $running,
                'source_type' => $entry->voucher?->source_type,
                'source_id' => $entry->voucher?->source_id,
            ];
        }

        return [
            'opening' => $opening,
            'rows' => $rows,
            'closing' => $running,
            'totals' => [
                'debit' => round($periodDebit, 2),
                'credit' => round($periodCredit, 2),
            ],
        ];
    }
}
