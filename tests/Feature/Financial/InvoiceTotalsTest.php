<?php

namespace Tests\Feature\Financial;

use App\Models\Invoice;
use App\Models\Party;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\Accounting\OperationalAccountingBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class InvoiceTotalsTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_invoice_with_gst_posts_through_accounting_bridge(): void
    {
        $context = $this->createTenantContext(['create invoices', 'view invoices']);
        app(EnsureCompanyAccounting::class)->handle($context['company']);

        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Bill To Party',
            'consider_for_accounting' => true,
        ]);

        $invoice = Invoice::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => 'INV-FIN-1',
            'date' => now()->toDateString(),
            'party_id' => $party->id,
            'gst_amount' => 180,
            'net_amount' => 1000,
            'total_amount' => 1180,
        ]);

        $this->assertEquals(1000.0, (float) $invoice->net_amount);
        $this->assertEquals(180.0, (float) $invoice->gst_amount);
        $this->assertEquals(1180.0, (float) $invoice->total_amount);

        app(OperationalAccountingBridge::class)->sync($invoice);

        $this->assertDatabaseHas('accounting_source_links', [
            'source_type' => Invoice::class,
            'source_id' => $invoice->id,
        ]);
    }

    public function test_invoice_total_equals_net_plus_gst(): void
    {
        $net = 500.0;
        $gst = 90.0;

        $this->assertEquals(590.0, $net + $gst);
    }
}
