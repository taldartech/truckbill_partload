<?php

namespace App\Services\Accounting;

use App\Enums\AccountingVoucherStatus;
use App\Models\AccountingSourceLink;
use App\Models\AccountingVoucher;
use App\Models\BankAccount;
use App\Models\Bilty;
use App\Models\Challan;
use App\Models\DeliveryReceipt;
use App\Models\Invoice;
use App\Models\InwardRegister;
use App\Models\MainChallan;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Describes the link between business documents and their vouchers for display.
 */
class AccountingAuditTrail
{
    /**
     * @var array<class-string<Model>, array{module: string, route: string|null, number: string}>
     */
    private const SOURCES = [
        Bilty::class => ['module' => 'Bilty', 'route' => 'bilties.show', 'number' => 'bilty_number'],
        Invoice::class => ['module' => 'Invoice', 'route' => 'invoices.show', 'number' => 'invoice_no'],
        DeliveryReceipt::class => ['module' => 'Delivery Receipt', 'route' => 'delivery-receipts.show', 'number' => 'delivery_receipt_no'],
        InwardRegister::class => ['module' => 'Inward Register', 'route' => 'inward-registers.show', 'number' => 'inward_register_no'],
        Challan::class => ['module' => 'Challan', 'route' => 'challans.show', 'number' => 'challan_number'],
        MainChallan::class => ['module' => 'Main Challan', 'route' => 'main-challans.show', 'number' => 'main_challan_number'],
        BankAccount::class => ['module' => 'Bank Account', 'route' => 'bank-accounts.show', 'number' => 'name'],
        Voucher::class => ['module' => 'Legacy Voucher', 'route' => null, 'number' => 'voucher_number'],
    ];

    public static function eventLabel(?string $eventKey): ?string
    {
        if ($eventKey === null) {
            return null;
        }

        if (str_starts_with($eventKey, 'manual.')) {
            return 'Manual: '.ucwords(str_replace('_', ' ', substr($eventKey, 7)));
        }

        return AccountingMapper::EVENT_LABELS[$eventKey] ?? $eventKey;
    }

    /**
     * @return array{module: string, number: string|null, url: string|null, deleted: bool, inactive: bool}|null
     */
    public function sourceReference(AccountingVoucher $voucher): ?array
    {
        return $this->sourceReferences(collect([$voucher]))[$voucher->id] ?? null;
    }

    /**
     * Source references keyed by voucher id, loading each source type in one query.
     *
     * @param  Collection<int, AccountingVoucher>  $vouchers
     * @return array<int, array{module: string, number: string|null, url: string|null, deleted: bool, inactive: bool}>
     */
    public function sourceReferences(Collection $vouchers): array
    {
        $references = [];

        foreach ($vouchers->whereNotNull('source_type')->groupBy('source_type') as $sourceType => $group) {
            $config = self::SOURCES[$sourceType] ?? null;
            $sources = class_exists($sourceType)
                ? $sourceType::query()
                    ->withoutGlobalScopes()
                    ->whereIn('company_id', $group->pluck('company_id')->unique())
                    ->whereIn('id', $group->pluck('source_id')->unique())
                    ->get()
                    ->keyBy('id')
                : collect();

            foreach ($group as $voucher) {
                $source = $sources->get($voucher->source_id);
                if ($source !== null && (int) $source->company_id !== (int) $voucher->company_id) {
                    $source = null;
                }
                $deleted = $source === null || (method_exists($source, 'trashed') && $source->trashed());

                $references[$voucher->id] = [
                    'module' => $config['module'] ?? class_basename($sourceType),
                    'number' => $source && $config ? (string) $source->{$config['number']} : '#'.$voucher->source_id,
                    'url' => ! $deleted && ($config['route'] ?? null) ? route($config['route'], $source) : null,
                    'deleted' => $deleted,
                    'inactive' => $source !== null && ($source->status ?? null) === 'Cancelled',
                ];
            }
        }

        return $references;
    }

    /**
     * @return array{module: string, number: string, url: string|null}
     */
    public function documentReference(Model $document): array
    {
        $config = self::SOURCES[$document::class] ?? null;
        $deleted = method_exists($document, 'trashed') && $document->trashed();

        return [
            'module' => $config['module'] ?? class_basename($document),
            'number' => $config ? (string) ($document->{$config['number']} ?: '#'.$document->getKey()) : '#'.$document->getKey(),
            'url' => ! $deleted && ($config['route'] ?? null) ? route($config['route'], $document) : null,
        ];
    }

    /**
     * Every voucher (active, reversed and reversal) posted for a document, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function panelFor(Model $document): array
    {
        $activeVoucherIds = AccountingSourceLink::query()
            ->withoutGlobalScopes()
            ->where('source_type', $document::class)
            ->where('source_id', $document->getKey())
            ->whereNotNull('voucher_id')
            ->pluck('voucher_id')
            ->all();

        return AccountingVoucher::query()
            ->withoutGlobalScopes()
            ->where('company_id', $document->company_id)
            ->where('source_type', $document::class)
            ->where('source_id', $document->getKey())
            ->with(['entries.account:id,name', 'createdBy:id,name', 'reversedBy:id,name'])
            ->latest('id')
            ->get()
            ->map(fn (AccountingVoucher $voucher): array => [
                'id' => $voucher->id,
                'voucher_number' => $voucher->voucher_number,
                'date' => $voucher->date?->toDateString(),
                'voucher_type' => $voucher->voucher_type?->label(),
                'status' => $voucher->status?->value,
                'event_label' => self::eventLabel($voucher->event_key),
                'state' => $this->state($voucher, $activeVoucherIds),
                'created_by' => $voucher->createdBy?->name,
                'reversed_by' => $voucher->reversedBy?->name,
                'reversed_at' => $voucher->reversed_at?->toDateTimeString(),
                'total' => $voucher->totalDebit(),
                'entries' => $voucher->entries->map(fn ($entry): array => [
                    'account_id' => $entry->account_id,
                    'account' => $entry->account?->name,
                    'debit' => (float) $entry->debit,
                    'credit' => (float) $entry->credit,
                ])->all(),
            ])
            ->all();
    }

    /**
     * @param  list<int>  $activeVoucherIds
     */
    private function state(AccountingVoucher $voucher, array $activeVoucherIds): string
    {
        return match (true) {
            $voucher->status === AccountingVoucherStatus::Cancelled => 'cancelled',
            $voucher->reversed_voucher_id !== null => 'reversal',
            $voucher->reversed_at !== null => 'reversed',
            in_array($voucher->id, $activeVoucherIds, true) => 'active',
            default => 'unlinked',
        };
    }
}
