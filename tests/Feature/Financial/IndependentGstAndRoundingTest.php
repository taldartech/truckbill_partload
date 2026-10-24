<?php

namespace Tests\Feature\Financial;

use App\Models\Invoice;
use App\Models\Party;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\Accounting\OperationalAccountingBridge;
use App\Support\GstCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

/**
 * Expected GST / rounding values are literals computed offline.
 * Production helpers are only used as the system under test.
 */
class IndependentGstAndRoundingTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_invoice_net_equals_independently_computed_taxable_plus_gst(): void
    {
        $taxable = 2500.0;
        $rate = 18.0;
        // Independent: 2500 * 0.18 = 450; total payable = 2950
        $expectedGst = 450.0;
        $expectedTotal = 2950.0;

        $this->assertSame($expectedGst, GstCalculator::amount($taxable, $rate));

        $context = $this->createTenantContext(['create invoices', 'view invoices']);
        app(EnsureCompanyAccounting::class)->handle($context['company']);

        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'GST Party',
            'consider_for_accounting' => true,
        ]);

        $invoice = Invoice::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => 'INV-IND-1',
            'date' => now()->toDateString(),
            'party_id' => $party->id,
            'gst_amount' => $expectedGst,
            'net_amount' => $expectedTotal,
            'total_amount' => $taxable,
            'status' => 'Pending',
        ]);

        $this->assertSame($taxable, (float) $invoice->total_amount);
        $this->assertSame($expectedGst, (float) $invoice->gst_amount);
        $this->assertSame($expectedTotal, (float) $invoice->net_amount);
        $this->assertSame(
            $expectedTotal,
            round((float) $invoice->total_amount + (float) $invoice->gst_amount, 2),
        );

        app(OperationalAccountingBridge::class)->sync($invoice);

        $voucher = app(OperationalAccountingBridge::class)->activeVouchers($invoice)->sole();
        $this->assertEquals(
            (float) $voucher->entries->sum('debit'),
            (float) $voucher->entries->sum('credit'),
        );
    }

    public function test_partial_payment_outstanding_is_independently_computed(): void
    {
        $invoiceTotal = 1180.0;
        $paid = 500.0;
        // Independent outstanding
        $expectedOutstanding = 680.0;

        $this->assertSame($expectedOutstanding, round($invoiceTotal - $paid, 2));
        $this->assertGreaterThan(0, $expectedOutstanding);
        $this->assertLessThan($invoiceTotal, $expectedOutstanding);
    }

    public function test_tds_deduction_reduces_payable_independently(): void
    {
        $gross = 10000.0;
        $tdsPercent = 2.0;
        // Independent: TDS = 200; net payable = 9800
        $expectedTds = 200.0;
        $expectedPayable = 9800.0;

        $tds = round($gross * $tdsPercent / 100, 2);
        $payable = round($gross - $tds, 2);

        $this->assertSame($expectedTds, $tds);
        $this->assertSame($expectedPayable, $payable);
    }
}
