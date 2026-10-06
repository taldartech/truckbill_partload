<?php

namespace App\Services\Accounting\Reports;

use App\Models\AccountingVoucher;
use App\Services\Accounting\AccountingAuditTrail;
use Illuminate\Support\Carbon;

class GstReport
{
    public const OUTPUT_KEYS = ['gst_output_cgst', 'gst_output_sgst', 'gst_output_igst'];

    public const INPUT_KEYS = ['gst_input'];

    public function __construct(
        private AccountTotals $totals,
        private AccountingAuditTrail $auditTrail,
    ) {}

    /**
     * Output GST is credit minus debit, input GST is debit minus credit, per ledger, with the source document of each line.
     *
     * @return array{ledgers: list<array<string, mixed>>, rows: list<array<string, mixed>>, totals: array{output: float, input: float, net_payable: float}}
     */
    public function build(int $companyId, Carbon|string $from, Carbon|string $to, ?int $branchId = null): array
    {
        $fromDate = Carbon::parse($from)->toDateString();
        $toDate = Carbon::parse($to)->toDateString();

        $accounts = $this->totals->accounts($companyId)
            ->filter(fn ($account) => in_array($account->system_key, [...self::OUTPUT_KEYS, ...self::INPUT_KEYS], true));

        if ($accounts->isEmpty()) {
            return ['ledgers' => [], 'rows' => [], 'totals' => ['output' => 0.0, 'input' => 0.0, 'net_payable' => 0.0]];
        }

        $entries = $this->totals->postedEntries($companyId, $branchId)
            ->whereIn('e.account_id', $accounts->keys()->all())
            ->whereDate('v.date', '>=', $fromDate)
            ->whereDate('v.date', '<=', $toDate)
            ->orderBy('v.date')
            ->orderBy('v.id')
            ->get(['e.account_id', 'e.debit', 'e.credit', 'v.id as voucher_id', 'v.date', 'v.voucher_number', 'v.narration']);

        $vouchers = AccountingVoucher::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $entries->pluck('voucher_id')->unique())
            ->get(['id', 'company_id', 'source_type', 'source_id']);
        $sources = $this->auditTrail->sourceReferences($vouchers);

        $ledgers = [];
        $rows = [];
        $totals = ['output' => 0.0, 'input' => 0.0];

        foreach ($entries as $entry) {
            $account = $accounts->get((int) $entry->account_id);
            $kind = in_array($account->system_key, self::OUTPUT_KEYS, true) ? 'output' : 'input';
            $amount = $kind === 'output'
                ? round((float) $entry->credit - (float) $entry->debit, 2)
                : round((float) $entry->debit - (float) $entry->credit, 2);

            $ledgers[$account->id] ??= ['account_id' => $account->id, 'account_name' => $account->name, 'kind' => $kind, 'amount' => 0.0];
            $ledgers[$account->id]['amount'] = round($ledgers[$account->id]['amount'] + $amount, 2);
            $totals[$kind] += $amount;

            $rows[] = [
                'date' => Carbon::parse($entry->date)->toDateString(),
                'voucher_id' => (int) $entry->voucher_id,
                'voucher_number' => $entry->voucher_number,
                'narration' => $entry->narration,
                'account_name' => $account->name,
                'kind' => $kind,
                'amount' => $amount,
                'source' => $sources[(int) $entry->voucher_id] ?? null,
            ];
        }

        return [
            'ledgers' => array_values($ledgers),
            'rows' => $rows,
            'totals' => [
                'output' => round($totals['output'], 2),
                'input' => round($totals['input'], 2),
                'net_payable' => round($totals['output'] - $totals['input'], 2),
            ],
        ];
    }
}
