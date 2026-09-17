<?php

namespace App\Services\Accounting\Reports;

use App\Models\AccountingVoucher;
use Illuminate\Support\Carbon;

class DayBookReport
{
    /**
     * @return list<array<string, mixed>>
     */
    public function build(int $companyId, Carbon|string $from, Carbon|string $to): array
    {
        $fromDate = Carbon::parse($from)->toDateString();
        $toDate = Carbon::parse($to)->toDateString();

        return AccountingVoucher::query()
            ->where('company_id', $companyId)
            ->posted()
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->with(['entries.account:id,name'])
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->map(fn (AccountingVoucher $voucher) => [
                'id' => $voucher->id,
                'date' => $voucher->date?->toDateString(),
                'voucher_number' => $voucher->voucher_number,
                'voucher_type' => $voucher->voucher_type?->value,
                'voucher_type_label' => $voucher->voucher_type?->label(),
                'narration' => $voucher->narration,
                'debit' => round((float) $voucher->entries->sum('debit'), 2),
                'credit' => round((float) $voucher->entries->sum('credit'), 2),
                'source_type' => $voucher->source_type,
                'source_id' => $voucher->source_id,
                'entries' => $voucher->entries->map(fn ($e) => [
                    'account_id' => $e->account_id,
                    'account_name' => $e->account?->name,
                    'debit' => (float) $e->debit,
                    'credit' => (float) $e->credit,
                ]),
            ])
            ->all();
    }
}
