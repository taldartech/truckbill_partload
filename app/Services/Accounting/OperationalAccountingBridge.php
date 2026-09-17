<?php

namespace App\Services\Accounting;

use App\Models\AccountingVoucher;
use App\Models\Bilty;
use App\Models\Challan;
use App\Models\Invoice;
use App\Models\InwardRegister;
use Illuminate\Database\Eloquent\Model;

class OperationalAccountingBridge
{
    public function __construct(
        private AccountingMapper $mapper,
        private VoucherService $vouchers,
    ) {}

    public function sync(Model $document): ?AccountingVoucher
    {
        $eventKey = match ($document::class) {
            Invoice::class => 'invoice.freight',
            Bilty::class => 'bilty.cartage_due',
            InwardRegister::class => 'inward.agent_crossing',
            Challan::class => 'challan.agent_settlement',
            default => null,
        };

        if ($eventKey === null) {
            return null;
        }

        $this->vouchers->reverseSourceVouchers($document);

        $mapped = $this->mapper->map($eventKey, $document->fresh() ?? $document);

        if ($mapped === null || empty($mapped['lines'])) {
            return null;
        }

        return $this->vouchers->post([
            'company_id' => $document->company_id,
            'branch_id' => $document->branch_id ?? null,
            'voucher_type' => $mapped['voucher_type'],
            'date' => $document->date ?? now()->toDateString(),
            'narration' => $mapped['narration'] ?? null,
            'source_type' => $document::class,
            'source_id' => $document->getKey(),
        ], $mapped['lines']);
    }

    public function clear(Model $document): void
    {
        $this->vouchers->reverseSourceVouchers($document);
    }
}
