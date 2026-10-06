<?php

namespace App\Services\Accounting\Reports;

use App\Models\AccountingVoucher;
use App\Services\Accounting\AccountingAuditTrail;
use Illuminate\Support\Carbon;

/**
 * Posted vouchers for a period; with a voucher type it doubles as the Journal / Voucher Register.
 */
class DayBookReport
{
    public function __construct(private AccountingAuditTrail $auditTrail) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function build(int $companyId, Carbon|string $from, Carbon|string $to, ?int $branchId = null, ?string $voucherType = null): array
    {
        $fromDate = Carbon::parse($from)->toDateString();
        $toDate = Carbon::parse($to)->toDateString();

        $vouchers = AccountingVoucher::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->when($voucherType !== null, fn ($query) => $query->where('voucher_type', $voucherType))
            ->posted()
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->with(['entries.account' => fn ($query) => $query->withoutGlobalScopes()->select('id', 'name')])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $sources = $this->auditTrail->sourceReferences($vouchers);

        return $vouchers
            ->map(fn (AccountingVoucher $voucher) => [
                'id' => $voucher->id,
                'date' => $voucher->date?->toDateString(),
                'voucher_number' => $voucher->voucher_number,
                'voucher_type' => $voucher->voucher_type?->value,
                'voucher_type_label' => $voucher->voucher_type?->label(),
                'narration' => $voucher->narration,
                'event_label' => AccountingAuditTrail::eventLabel($voucher->event_key),
                'source' => $sources[$voucher->id] ?? null,
                'is_reversal' => $voucher->reversed_voucher_id !== null,
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
