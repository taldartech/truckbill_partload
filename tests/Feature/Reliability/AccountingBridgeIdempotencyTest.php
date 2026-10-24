<?php

namespace Tests\Feature\Reliability;

use App\Models\AccountingSourceLink;
use App\Models\AccountingVoucher;
use App\Models\Invoice;
use App\Models\Party;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\Accounting\OperationalAccountingBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AccountingBridgeIdempotencyTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_unchanged_invoice_resync_does_not_create_duplicate_vouchers(): void
    {
        $context = $this->createTenantContext(['view invoices']);
        app(EnsureCompanyAccounting::class)->handle($context['company']);

        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Idempotent Party',
            'consider_for_accounting' => true,
        ]);

        $invoice = Invoice::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => 'INV-IDEM-1',
            'date' => now()->toDateString(),
            'party_id' => $party->id,
            'status' => 'Pending',
            'net_amount' => 1000,
            'total_amount' => 1000,
        ]);

        $bridge = app(OperationalAccountingBridge::class);

        $this->assertSame(1, $bridge->sync($invoice));
        $vouchersAfterFirst = AccountingVoucher::query()->count();
        $linksAfterFirst = AccountingSourceLink::query()->count();

        $this->assertSame(0, $bridge->sync($invoice->fresh()));
        $this->assertSame(0, $bridge->sync($invoice->fresh()));

        $this->assertSame($vouchersAfterFirst, AccountingVoucher::query()->count());
        $this->assertSame($linksAfterFirst, AccountingSourceLink::query()->count());
        $this->assertCount(1, $bridge->activeVouchers($invoice));
    }
}
