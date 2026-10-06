<?php

namespace App\Services\Accounting;

use App\Enums\AccountingVoucherStatus;
use App\Models\AccountingVoucher;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only consistency checks between business documents and the ledger.
 */
class AccountingIntegrity
{
    public const CHECKS = [
        'missing' => 'Documents expected to post that have no active voucher',
        'stale' => 'Active vouchers that no longer match their document',
        'orphans' => 'Active vouchers whose source is deleted or cancelled',
        'duplicates' => 'More than one active voucher for the same document and event',
        'unbalanced' => 'Vouchers whose debits and credits differ',
        'tenant_mismatch' => 'Voucher company or branch differs from its source document',
        'outside_financial_year' => 'Voucher date outside its financial year',
        'cross_company_accounts' => 'Entries posted to another company\'s accounts',
    ];

    public function __construct(
        private OperationalAccountingBridge $bridge,
        private AccountingAuditTrail $auditTrail,
    ) {}

    /**
     * Every document of the company that can post, optionally limited to classes owning an event.
     *
     * @return Generator<int, Model>
     */
    public function documents(int $companyId, ?string $eventKey = null): Generator
    {
        foreach (AccountingMapper::EVENTS as $class => $events) {
            if ($eventKey !== null && ! in_array($eventKey, $events, true)) {
                continue;
            }

            foreach ($class::query()->withoutGlobalScopes()->where('company_id', $companyId)->lazyById(200) as $document) {
                yield $document;
            }
        }
    }

    /**
     * @return array{company_id: int, total: int, checks: array<string, array{label: string, count: int, items: list<array<string, mixed>>}>}
     */
    public function audit(int $companyId, int $itemLimit = 100): array
    {
        $results = array_map(fn (string $label) => ['label' => $label, 'count' => 0, 'items' => []], self::CHECKS);
        $add = function (string $check, array $item) use (&$results, $itemLimit): void {
            $results[$check]['count']++;
            if (count($results[$check]['items']) < $itemLimit) {
                $results[$check]['items'][] = $item;
            }
        };

        $this->checkDocuments($companyId, $add);

        $openVouchers = $this->openSourceVouchers($companyId);
        $this->checkOrphans($openVouchers, $add);
        $this->checkDuplicates($openVouchers, $add);
        $this->checkTenantMismatch($openVouchers, $add);
        $this->checkUnbalanced($companyId, $add);
        $this->checkFinancialYears($companyId, $add);
        $this->checkCrossCompanyAccounts($companyId, $add);

        return [
            'company_id' => $companyId,
            'total' => array_sum(array_column($results, 'count')),
            'checks' => $results,
        ];
    }

    private function checkDocuments(int $companyId, callable $add): void
    {
        foreach ($this->documents($companyId) as $document) {
            if ($this->bridge->isInactive($document)) {
                continue;
            }

            foreach ($this->bridge->preview($document)['events'] as $eventKey => $action) {
                $check = match ($action) {
                    'post' => 'missing',
                    'repost', 'reverse' => 'stale',
                    default => null,
                };

                if ($check !== null) {
                    $add($check, [
                        ...$this->auditTrail->documentReference($document),
                        'event' => AccountingAuditTrail::eventLabel($eventKey),
                        'detail' => $action === 'reverse' ? 'Document no longer produces this entry' : null,
                    ]);
                }
            }
        }
    }

    /**
     * @param  Collection<int, AccountingVoucher>  $vouchers
     */
    private function checkOrphans(Collection $vouchers, callable $add): void
    {
        foreach ($this->auditTrail->sourceReferences($vouchers) as $voucherId => $source) {
            if ($source['deleted'] || $source['inactive']) {
                $add('orphans', [
                    ...$source,
                    'voucher_id' => $voucherId,
                    'voucher_number' => $vouchers->firstWhere('id', $voucherId)?->voucher_number,
                    'detail' => $source['deleted'] ? 'Source deleted' : 'Source cancelled',
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, AccountingVoucher>  $vouchers
     */
    private function checkDuplicates(Collection $vouchers, callable $add): void
    {
        $groups = $vouchers->groupBy(fn (AccountingVoucher $voucher) => implode('|', [
            $voucher->source_type,
            $voucher->source_id,
            $voucher->event_key ?? AccountingMapper::LEGACY_EVENTS[$voucher->source_type] ?? 'unknown',
        ]));

        foreach ($groups as $group) {
            if ($group->count() < 2) {
                continue;
            }

            $first = $group->first();
            $add('duplicates', [
                ...($this->auditTrail->sourceReference($first) ?? []),
                'event' => AccountingAuditTrail::eventLabel($first->event_key ?? AccountingMapper::LEGACY_EVENTS[$first->source_type] ?? null),
                'voucher_numbers' => $group->pluck('voucher_number')->all(),
                'detail' => $group->count().' active vouchers',
            ]);
        }
    }

    /**
     * @param  Collection<int, AccountingVoucher>  $vouchers
     */
    private function checkTenantMismatch(Collection $vouchers, callable $add): void
    {
        foreach ($vouchers->groupBy('source_type') as $sourceType => $group) {
            if (! class_exists($sourceType)) {
                continue;
            }

            $sources = $sourceType::query()
                ->withoutGlobalScopes()
                ->whereIn('id', $group->pluck('source_id')->unique())
                ->get()
                ->keyBy('id');

            foreach ($group as $voucher) {
                $source = $sources->get($voucher->source_id);
                if ($source === null) {
                    continue;
                }

                $companyDiffers = (int) $source->company_id !== (int) $voucher->company_id;
                $sourceBranch = $source->getAttribute('branch_id');
                $branchDiffers = $sourceBranch !== null && (int) $sourceBranch !== (int) $voucher->branch_id;

                if ($companyDiffers || $branchDiffers) {
                    $add('tenant_mismatch', [
                        ...$this->auditTrail->documentReference($source),
                        'voucher_id' => $voucher->id,
                        'voucher_number' => $voucher->voucher_number,
                        'detail' => $companyDiffers ? 'Company differs' : 'Branch differs',
                    ]);
                }
            }
        }
    }

    private function checkUnbalanced(int $companyId, callable $add): void
    {
        DB::table('accounting_vouchers as v')
            ->leftJoin('accounting_voucher_entries as e', 'e.voucher_id', '=', 'v.id')
            ->where('v.company_id', $companyId)
            ->where('v.status', AccountingVoucherStatus::Posted->value)
            ->groupBy('v.id', 'v.voucher_number')
            ->havingRaw('COUNT(e.id) < 2 OR ABS(COALESCE(SUM(e.debit), 0) - COALESCE(SUM(e.credit), 0)) >= 0.005')
            ->selectRaw('v.id, v.voucher_number, COALESCE(SUM(e.debit), 0) AS debit, COALESCE(SUM(e.credit), 0) AS credit, COUNT(e.id) AS line_count')
            ->get()
            ->each(fn (object $row) => $add('unbalanced', [
                'voucher_id' => (int) $row->id,
                'voucher_number' => $row->voucher_number,
                'detail' => sprintf('Dr %.2f / Cr %.2f across %d lines', $row->debit, $row->credit, $row->line_count),
            ]));
    }

    private function checkFinancialYears(int $companyId, callable $add): void
    {
        DB::table('accounting_vouchers as v')
            ->leftJoin('financial_years as f', 'f.id', '=', 'v.financial_year_id')
            ->where('v.company_id', $companyId)
            ->where(fn ($query) => $query
                ->whereNull('f.id')
                ->orWhereColumn('f.company_id', '!=', 'v.company_id')
                ->orWhereRaw('DATE(v.date) < f.starts_on')
                ->orWhereRaw('DATE(v.date) > f.ends_on'))
            ->get(['v.id', 'v.voucher_number', 'v.date', 'f.name'])
            ->each(fn (object $row) => $add('outside_financial_year', [
                'voucher_id' => (int) $row->id,
                'voucher_number' => $row->voucher_number,
                'detail' => 'Dated '.substr((string) $row->date, 0, 10).' in '.($row->name ?? 'no financial year'),
            ]));
    }

    private function checkCrossCompanyAccounts(int $companyId, callable $add): void
    {
        DB::table('accounting_voucher_entries as e')
            ->join('accounting_vouchers as v', 'v.id', '=', 'e.voucher_id')
            ->join('accounts as a', 'a.id', '=', 'e.account_id')
            ->where('v.company_id', $companyId)
            ->whereColumn('a.company_id', '!=', 'v.company_id')
            ->get(['v.id', 'v.voucher_number', 'a.name', 'a.company_id'])
            ->each(fn (object $row) => $add('cross_company_accounts', [
                'voucher_id' => (int) $row->id,
                'voucher_number' => $row->voucher_number,
                'detail' => "Account {$row->name} belongs to company #{$row->company_id}",
            ]));
    }

    /**
     * Posted, not reversed, non-reversal vouchers created from documents.
     *
     * @return Collection<int, AccountingVoucher>
     */
    private function openSourceVouchers(int $companyId): Collection
    {
        return AccountingVoucher::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNotNull('source_type')
            ->where('status', AccountingVoucherStatus::Posted)
            ->whereNull('reversed_voucher_id')
            ->whereNull('reversed_at')
            ->whereDoesntHave('reversals', fn (Builder $query) => $query->withoutGlobalScopes())
            ->get(['id', 'company_id', 'branch_id', 'voucher_number', 'source_type', 'source_id', 'event_key']);
    }
}
