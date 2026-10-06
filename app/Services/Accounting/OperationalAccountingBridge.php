<?php

namespace App\Services\Accounting;

use App\Enums\AccountingVoucherStatus;
use App\Enums\AccountingVoucherType;
use App\Models\AccountingSourceLink;
use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Keeps one posted voucher per (document, event). Re-syncing an unchanged
 * document is a no-op; a changed one reverses the old voucher and posts anew.
 */
class OperationalAccountingBridge
{
    public function __construct(
        private AccountingMapper $mapper,
        private VoucherService $vouchers,
    ) {}

    public function supports(Model $document): bool
    {
        return $this->mapper->eventsFor($document) !== [];
    }

    /**
     * @param  list<string>|null  $onlyEvents  Limit to these events; vouchers of other events are left as they are.
     * @return int Number of events whose posting changed.
     */
    public function sync(Model $document, ?array $onlyEvents = null): int
    {
        if (! $this->supports($document)) {
            return 0;
        }

        $document = $document->fresh() ?? $document;

        if ($this->isInactive($document)) {
            return $this->clear($document);
        }

        return DB::transaction(function () use ($document, $onlyEvents) {
            $changed = 0;
            $linkedVoucherIds = [];

            foreach ($this->mapper->eventsFor($document) as $eventKey) {
                if ($onlyEvents !== null && ! in_array($eventKey, $onlyEvents, true)) {
                    $linkedVoucherIds = [...$linkedVoucherIds, ...$this->otherEventVoucherIds($document, $eventKey)];

                    continue;
                }

                $mapped = $this->mapper->map($eventKey, $document);
                $link = $this->syncEvent($document, $eventKey, $mapped, $changed);

                if ($link?->voucher_id) {
                    $linkedVoucherIds[] = (int) $link->voucher_id;
                }
            }

            $changed += $this->reverseOrphans($document, $linkedVoucherIds);

            return $changed;
        });
    }

    /**
     * @return int Number of vouchers reversed.
     */
    public function clear(Model $document): int
    {
        return DB::transaction(function () use ($document) {
            $reversed = 0;

            $links = AccountingSourceLink::query()
                ->withoutGlobalScopes()
                ->where('source_type', $document::class)
                ->where('source_id', $document->getKey())
                ->lockForUpdate()
                ->get();

            foreach ($links as $link) {
                $voucher = $this->openVoucher($link->voucher_id);
                if ($voucher) {
                    $this->vouchers->reverse($voucher);
                    $reversed++;
                }

                if ($link->voucher_id !== null) {
                    $link->update(['voucher_id' => null, 'fingerprint' => null, 'posted_by' => Auth::id()]);
                }
            }

            return $reversed + $this->reverseOrphans($document, []);
        });
    }

    /**
     * What sync() would do for each event, without writing anything. Mapping can
     * create missing ledgers, so the work runs in a transaction that is rolled back.
     *
     * @return array{events: array<string, 'post'|'repost'|'reverse'|'unchanged'|'none'>, orphans: int, unlinked: int}
     */
    public function preview(Model $document): array
    {
        if (! $this->supports($document)) {
            return ['events' => [], 'orphans' => 0, 'unlinked' => 0];
        }

        DB::beginTransaction();

        try {
            $document = $document->fresh() ?? $document;
            $inactive = $this->isInactive($document);
            $events = [];
            $linkedVoucherIds = [];
            $unlinked = 0;

            foreach ($this->mapper->eventsFor($document) as $eventKey) {
                $link = AccountingSourceLink::query()
                    ->withoutGlobalScopes()
                    ->where('source_type', $document::class)
                    ->where('source_id', $document->getKey())
                    ->where('event_key', $eventKey)
                    ->first();

                $current = $link
                    ? $this->openVoucher($link->voucher_id)
                    : ($this->mapper->legacyEventFor($document) === $eventKey ? $this->legacyOpenVoucher($document, $eventKey) : null);
                $currentFingerprint = $link ? $link->fingerprint : ($current ? $this->voucherFingerprint($current) : null);

                if ($current) {
                    $linkedVoucherIds[] = $current->id;
                    $unlinked += $link ? 0 : 1;
                }

                if ($inactive) {
                    $events[$eventKey] = $current ? 'reverse' : 'none';

                    continue;
                }

                $mapped = $this->mapper->map($eventKey, $document);
                $hasLines = $mapped !== null && ! empty($mapped['lines']);
                $fingerprint = $hasLines
                    ? $this->fingerprint($mapped['voucher_type'], $mapped['date'] ?? $this->documentDate($document), $mapped['narration'] ?? null, $mapped['lines'])
                    : null;

                $events[$eventKey] = match (true) {
                    $current === null && ! $hasLines => 'none',
                    $current === null => 'post',
                    ! $hasLines => 'reverse',
                    $currentFingerprint === $fingerprint => 'unchanged',
                    default => 'repost',
                };
            }

            $orphans = AccountingVoucher::query()
                ->withoutGlobalScopes()
                ->where('source_type', $document::class)
                ->where('source_id', $document->getKey())
                ->whereNotIn('id', $linkedVoucherIds)
                ->where('status', AccountingVoucherStatus::Posted)
                ->whereNull('reversed_voucher_id')
                ->whereNull('reversed_at')
                ->whereDoesntHave('reversals', fn ($query) => $query->withoutGlobalScopes())
                ->count();

            return ['events' => $events, 'orphans' => $orphans, 'unlinked' => $unlinked];
        } finally {
            DB::rollBack();
        }
    }

    public function isInactive(Model $document): bool
    {
        if (method_exists($document, 'trashed') && $document->trashed()) {
            return true;
        }

        return strcasecmp((string) ($document->getAttribute('status') ?? ''), 'Cancelled') === 0;
    }

    /**
     * @return Collection<int, AccountingVoucher>
     */
    public function activeVouchers(Model $document): Collection
    {
        $voucherIds = AccountingSourceLink::query()
            ->withoutGlobalScopes()
            ->where('source_type', $document::class)
            ->where('source_id', $document->getKey())
            ->whereNotNull('voucher_id')
            ->pluck('voucher_id');

        return AccountingVoucher::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $voucherIds)
            ->where('status', AccountingVoucherStatus::Posted)
            ->with('entries.account')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array{voucher_type: AccountingVoucherType, narration: ?string, date?: ?string, lines: list<array{account_id:int,debit:float|int|string,credit:float|int|string,narration?:?string}>}|null  $mapped
     */
    private function syncEvent(Model $document, string $eventKey, ?array $mapped, int &$changed): ?AccountingSourceLink
    {
        $link = AccountingSourceLink::query()
            ->withoutGlobalScopes()
            ->where('source_type', $document::class)
            ->where('source_id', $document->getKey())
            ->where('event_key', $eventKey)
            ->lockForUpdate()
            ->first()
            ?? $this->adoptLegacyVoucher($document, $eventKey);

        $current = $this->openVoucher($link?->voucher_id);
        $hasLines = $mapped !== null && ! empty($mapped['lines']);
        $date = $hasLines ? ($mapped['date'] ?? $this->documentDate($document)) : null;
        $fingerprint = $hasLines
            ? $this->fingerprint($mapped['voucher_type'], $date, $mapped['narration'] ?? null, $mapped['lines'])
            : null;

        if ($current === null && ! $hasLines) {
            if ($link?->voucher_id !== null) {
                $link->update(['voucher_id' => null, 'fingerprint' => null]);
            }

            return $link;
        }

        if ($current !== null && $link->fingerprint === $fingerprint) {
            return $link;
        }

        if ($current !== null) {
            $this->vouchers->reverse($current);
        }

        $voucher = $hasLines
            ? $this->vouchers->post([
                'company_id' => $document->company_id,
                'branch_id' => $document->branch_id ?? null,
                'voucher_type' => $mapped['voucher_type'],
                'date' => $date,
                'narration' => $mapped['narration'] ?? null,
                'source_type' => $document::class,
                'source_id' => $document->getKey(),
                'event_key' => $eventKey,
            ], $mapped['lines'])
            : null;

        $link ??= new AccountingSourceLink([
            'source_type' => $document::class,
            'source_id' => $document->getKey(),
            'event_key' => $eventKey,
        ]);

        $link->fill([
            'company_id' => $document->company_id,
            'branch_id' => $document->branch_id ?? null,
            'voucher_id' => $voucher?->id,
            'fingerprint' => $fingerprint,
            'posted_by' => Auth::id(),
        ])->save();

        $changed++;

        return $link;
    }

    /**
     * Vouchers posted before source links existed carry no event key; the
     * document's legacy event takes ownership of the newest open one.
     */
    private function adoptLegacyVoucher(Model $document, string $eventKey): ?AccountingSourceLink
    {
        if ($this->mapper->legacyEventFor($document) !== $eventKey) {
            return null;
        }

        $legacy = $this->legacyOpenVoucher($document, $eventKey);

        if (! $legacy) {
            return null;
        }

        $legacy->update(['event_key' => $eventKey]);

        return AccountingSourceLink::query()->withoutGlobalScopes()->create([
            'company_id' => $legacy->company_id,
            'branch_id' => $legacy->branch_id,
            'source_type' => $document::class,
            'source_id' => $document->getKey(),
            'event_key' => $eventKey,
            'voucher_id' => $legacy->id,
            'fingerprint' => $this->voucherFingerprint($legacy),
            'posted_by' => $legacy->created_by,
        ]);
    }

    /**
     * Open vouchers owned by an event that the current sync is not touching.
     *
     * @return list<int>
     */
    private function otherEventVoucherIds(Model $document, string $eventKey): array
    {
        $linked = AccountingSourceLink::query()
            ->withoutGlobalScopes()
            ->where('source_type', $document::class)
            ->where('source_id', $document->getKey())
            ->where('event_key', $eventKey)
            ->value('voucher_id');

        if ($linked) {
            return [(int) $linked];
        }

        $legacy = $this->mapper->legacyEventFor($document) === $eventKey
            ? $this->legacyOpenVoucher($document, $eventKey)
            : null;

        return $legacy ? [$legacy->id] : [];
    }

    private function legacyOpenVoucher(Model $document, string $eventKey): ?AccountingVoucher
    {
        return AccountingVoucher::query()
            ->withoutGlobalScopes()
            ->where('source_type', $document::class)
            ->where('source_id', $document->getKey())
            ->where(fn ($query) => $query->whereNull('event_key')->orWhere('event_key', $eventKey))
            ->where('status', AccountingVoucherStatus::Posted)
            ->whereNull('reversed_voucher_id')
            ->whereNull('reversed_at')
            ->whereDoesntHave('reversals', fn ($query) => $query->withoutGlobalScopes())
            ->latest('id')
            ->first();
    }

    /**
     * @param  list<int>  $keepVoucherIds
     */
    private function reverseOrphans(Model $document, array $keepVoucherIds): int
    {
        $orphans = AccountingVoucher::query()
            ->withoutGlobalScopes()
            ->where('source_type', $document::class)
            ->where('source_id', $document->getKey())
            ->whereNotIn('id', $keepVoucherIds)
            ->where('status', AccountingVoucherStatus::Posted)
            ->whereNull('reversed_voucher_id')
            ->whereNull('reversed_at')
            ->whereDoesntHave('reversals', fn ($query) => $query->withoutGlobalScopes())
            ->orderBy('id')
            ->get();

        $orphans->each(fn (AccountingVoucher $voucher) => $this->vouchers->reverse($voucher));

        return $orphans->count();
    }

    private function openVoucher(?int $voucherId): ?AccountingVoucher
    {
        if (! $voucherId) {
            return null;
        }

        $voucher = AccountingVoucher::query()->withoutGlobalScopes()->find($voucherId);

        return $voucher?->isOpenForReversal() ? $voucher : null;
    }

    private function documentDate(Model $document): string
    {
        $date = $document->getAttribute('date');

        return $date ? Carbon::parse($date)->toDateString() : now()->toDateString();
    }

    /**
     * @param  list<array{account_id:int,debit?:float|int|string,credit?:float|int|string}>  $lines
     */
    public function fingerprint(AccountingVoucherType|string $type, string $date, ?string $narration, array $lines): string
    {
        $type = $type instanceof AccountingVoucherType ? $type->value : $type;

        $normalizedLines = array_map(fn (array $line) => [
            (int) $line['account_id'],
            VoucherService::toPaise($line['debit'] ?? 0),
            VoucherService::toPaise($line['credit'] ?? 0),
        ], array_values($lines));

        return hash('sha256', json_encode([$type, Carbon::parse($date)->toDateString(), (string) $narration, $normalizedLines]));
    }

    private function voucherFingerprint(AccountingVoucher $voucher): string
    {
        $lines = $voucher->entries()->get()->map(fn (AccountingVoucherEntry $entry) => [
            'account_id' => $entry->account_id,
            'debit' => $entry->debit,
            'credit' => $entry->credit,
        ])->all();

        return $this->fingerprint($voucher->voucher_type, $voucher->date->toDateString(), $voucher->narration, $lines);
    }
}
