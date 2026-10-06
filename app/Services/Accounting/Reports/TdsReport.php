<?php

namespace App\Services\Accounting\Reports;

use Illuminate\Support\Carbon;

class TdsReport
{
    private const COUNTERPARTY_SYSTEM_KEYS = ['sundry_debtors_unlinked', 'sundry_creditors_unlinked'];

    public function __construct(private AccountTotals $totals) {}

    /**
     * TDS receivable (deducted by customers) and payable (deducted by us), attributed to the party/agent ledger in the same voucher.
     *
     * @return array{rows: list<array{account_id: int|null, account_name: string, receivable: float, payable: float, vouchers: int}>, totals: array{receivable: float, payable: float}}
     */
    public function build(int $companyId, Carbon|string $from, Carbon|string $to, ?int $branchId = null): array
    {
        $fromDate = Carbon::parse($from)->toDateString();
        $toDate = Carbon::parse($to)->toDateString();

        $accounts = $this->totals->accounts($companyId);
        $receivable = $accounts->firstWhere('system_key', 'tds_receivable');
        $payable = $accounts->firstWhere('system_key', 'tds_payable');
        $tdsAccountIds = array_values(array_filter([$receivable?->id, $payable?->id]));

        if ($tdsAccountIds === []) {
            return ['rows' => [], 'totals' => ['receivable' => 0.0, 'payable' => 0.0]];
        }

        $tdsEntries = $this->totals->postedEntries($companyId, $branchId)
            ->whereIn('e.account_id', $tdsAccountIds)
            ->whereDate('v.date', '>=', $fromDate)
            ->whereDate('v.date', '<=', $toDate)
            ->get(['e.voucher_id', 'e.account_id', 'e.debit', 'e.credit']);

        $counterparties = $this->totals->postedEntries($companyId, $branchId)
            ->whereIn('e.voucher_id', $tdsEntries->pluck('voucher_id')->unique())
            ->whereNotIn('e.account_id', $tdsAccountIds)
            ->orderBy('e.line_no')
            ->get(['e.voucher_id', 'e.account_id'])
            ->filter(function (object $entry) use ($accounts): bool {
                $account = $accounts->get((int) $entry->account_id);

                return $account !== null
                    && ($account->reference_type !== null || in_array($account->system_key, self::COUNTERPARTY_SYSTEM_KEYS, true));
            })
            ->unique('voucher_id')
            ->keyBy('voucher_id');

        $rows = [];
        $totals = ['receivable' => 0.0, 'payable' => 0.0];

        foreach ($tdsEntries as $entry) {
            $counterpartyId = $counterparties->get($entry->voucher_id)?->account_id;
            $key = $counterpartyId ?? 0;
            $rows[$key] ??= [
                'account_id' => $counterpartyId !== null ? (int) $counterpartyId : null,
                'account_name' => $counterpartyId !== null ? $accounts->get((int) $counterpartyId)->name : 'Unallocated',
                'receivable' => 0.0,
                'payable' => 0.0,
                'voucher_ids' => [],
            ];

            if ((int) $entry->account_id === $receivable?->id) {
                $amount = round((float) $entry->debit - (float) $entry->credit, 2);
                $rows[$key]['receivable'] = round($rows[$key]['receivable'] + $amount, 2);
                $totals['receivable'] += $amount;
            } else {
                $amount = round((float) $entry->credit - (float) $entry->debit, 2);
                $rows[$key]['payable'] = round($rows[$key]['payable'] + $amount, 2);
                $totals['payable'] += $amount;
            }
            $rows[$key]['voucher_ids'][$entry->voucher_id] = true;
        }

        $rows = array_map(function (array $row): array {
            $row['vouchers'] = count($row['voucher_ids']);
            unset($row['voucher_ids']);

            return $row;
        }, array_values($rows));
        usort($rows, fn (array $a, array $b): int => $a['account_name'] <=> $b['account_name']);

        return [
            'rows' => $rows,
            'totals' => array_map(fn (float $value): float => round($value, 2), $totals),
        ];
    }
}
