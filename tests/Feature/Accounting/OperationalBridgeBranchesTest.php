<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountingSourceLink;
use App\Models\AccountingVoucher;
use App\Models\Invoice;
use App\Models\Party;
use App\Services\Accounting\EntityAccountLinker;
use App\Services\Accounting\OperationalAccountingBridge;
use App\Services\Accounting\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class OperationalBridgeBranchesTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    public function test_zeroing_a_posted_invoice_previews_and_applies_a_reversal(): void
    {
        $invoice = $this->postedInvoice();
        $bridge = app(OperationalAccountingBridge::class);

        $invoice->update(['net_amount' => 0, 'total_amount' => 0]);

        $this->assertSame(['invoice.freight' => 'reverse'], $bridge->preview($invoice)['events']);

        $this->assertSame(1, $bridge->sync($invoice));
        $this->assertNull(AccountingSourceLink::query()->withoutGlobalScopes()->sole()->voucher_id);
        $this->assertSame(['invoice.freight' => 'none'], $bridge->preview($invoice)['events']);
    }

    public function test_sync_clears_stale_link_when_voucher_was_reversed_outside_the_bridge(): void
    {
        $invoice = $this->postedInvoice();
        $bridge = app(OperationalAccountingBridge::class);

        app(VoucherService::class)->reverse(AccountingVoucher::query()->withoutGlobalScopes()->sole());
        $invoice->update(['net_amount' => 0, 'total_amount' => 0]);

        $this->assertSame(0, $bridge->sync($invoice));
        $this->assertNull(AccountingSourceLink::query()->withoutGlobalScopes()->sole()->voucher_id);
    }

    public function test_sync_limited_to_other_events_keeps_linked_voucher_untouched(): void
    {
        $invoice = $this->postedInvoice();
        $voucher = AccountingVoucher::query()->withoutGlobalScopes()->sole();

        $this->assertSame(0, app(OperationalAccountingBridge::class)->sync($invoice, ['bilty.paid_receipt']));
        $this->assertNull($voucher->fresh()->reversed_at);
    }

    private function postedInvoice(): Invoice
    {
        $context = $this->accountingContext();
        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Bridge Party',
            'address' => 'Road',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9000000008',
            'consider_for_accounting' => true,
        ]);
        app(EntityAccountLinker::class)->enable($party);

        $invoice = Invoice::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => 'INV-BRIDGE',
            'date' => now()->toDateString(),
            'party_id' => $party->id,
            'status' => 'Pending',
            'total_amount' => 1500,
            'net_amount' => 1500,
        ]);

        $this->assertSame(1, app(OperationalAccountingBridge::class)->sync($invoice));

        return $invoice;
    }
}
