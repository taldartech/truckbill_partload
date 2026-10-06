<?php

namespace App\Services\Accounting\Reports;

use App\Models\Account;
use Illuminate\Support\Carbon;

class AccountLedgerReport
{
    public function __construct(private AccountTotals $totals) {}

    /**
     * @return array{opening: float, rows: list<array<string, mixed>>, closing: float, totals: array{debit: float, credit: float}}
     */
    public function build(Account $account, Carbon|string $from, Carbon|string $to, ?int $branchId = null): array
    {
        $fromDate = Carbon::parse($from)->toDateString();
        $toDate = Carbon::parse($to)->toDateString();

        $sum = $this->totals->forPeriod($account->company_id, $fromDate, $toDate, $branchId, [$account->id])->get($account->id);
        $opening = $sum ? round($sum['opening_debit'] - $sum['opening_credit'], 2) : 0.0;

        $entries = $this->totals->postedEntries($account->company_id, $branchId)
            ->where('e.account_id', $account->id)
            ->whereDate('v.date', '>=', $fromDate)
            ->whereDate('v.date', '<=', $toDate)
            ->orderBy('v.date')
            ->orderBy('v.id')
            ->orderBy('e.line_no')
            ->get([
                'e.voucher_id', 'e.debit', 'e.credit', 'e.narration',
                'v.date', 'v.voucher_number', 'v.voucher_type', 'v.narration as voucher_narration', 'v.source_type', 'v.source_id',
            ]);

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
                'date' => Carbon::parse($entry->date)->toDateString(),
                'voucher_id' => (int) $entry->voucher_id,
                'voucher_number' => $entry->voucher_number,
                'voucher_type' => $entry->voucher_type,
                'particulars' => $entry->narration ?: $entry->voucher_narration,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $running,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
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
