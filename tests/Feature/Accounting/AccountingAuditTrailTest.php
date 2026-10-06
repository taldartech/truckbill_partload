<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountingVoucher;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\User;
use App\Services\Accounting\EntityAccountLinker;
use App\Services\Accounting\OperationalAccountingBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class AccountingAuditTrailTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    public function test_voucher_show_links_back_to_source_document(): void
    {
        $context = $this->accountingContext(['view accounting_vouchers', 'edit accounting_vouchers']);
        $invoice = $this->postedInvoice($context);
        $voucher = AccountingVoucher::query()->sole();

        $this->actingAs($context['user'])
            ->get(route('accounting.vouchers.show', $voucher))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/Vouchers/Show')
                ->where('isSystemGenerated', true)
                ->where('canReverse', true)
                ->where('eventLabel', 'Invoice freight')
                ->where('source.module', 'Invoice')
                ->where('source.number', 'INV-AUD-1')
                ->where('source.url', route('invoices.show', $invoice))
                ->where('source.deleted', false)
                ->where('voucher.created_by.name', $context['user']->name));
    }

    public function test_reversal_details_are_recorded_and_shown(): void
    {
        $context = $this->accountingContext(['view accounting_vouchers']);
        $invoice = $this->postedInvoice($context);
        $original = AccountingVoucher::query()->sole();

        $invoice->update(['net_amount' => 1200, 'total_amount' => 1200]);
        app(OperationalAccountingBridge::class)->sync($invoice);

        $original->refresh();
        $this->assertSame($context['user']->id, $original->reversed_by);
        $this->assertNotNull($original->reversed_at);

        $this->actingAs($context['user'])
            ->get(route('accounting.vouchers.show', $original))
            ->assertInertia(fn (Assert $page) => $page
                ->where('canReverse', false)
                ->has('voucher.reversals', 1)
                ->where('voucher.reversed_by.name', $context['user']->name));
    }

    public function test_document_show_page_lists_vouchers_for_accounting_users_only(): void
    {
        $context = $this->accountingContext(['view invoices', 'view accounting_vouchers']);
        $invoice = $this->postedInvoice($context);
        $invoice->update(['net_amount' => 1200, 'total_amount' => 1200]);
        app(OperationalAccountingBridge::class)->sync($invoice);

        $this->actingAs($context['user'])
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invoices/Show')
                ->has('accounting', 3)
                ->where('accounting.0.state', 'active')
                ->where('accounting.0.event_label', 'Invoice freight')
                ->where('accounting.1.state', 'reversal')
                ->where('accounting.2.state', 'reversed'));

        $operator = User::factory()->create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
        ]);
        $operator->givePermissionTo('view invoices');

        $this->actingAs($operator)
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('accounting', null));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function postedInvoice(array $context): Invoice
    {
        $this->actingAs($context['user']);

        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Audit Party',
            'address' => 'Road',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9000000003',
            'consider_for_accounting' => true,
        ]);
        app(EntityAccountLinker::class)->enable($party);

        $invoice = Invoice::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => 'INV-AUD-1',
            'date' => now()->toDateString(),
            'party_id' => $party->id,
            'status' => 'Pending',
            'total_amount' => 1000,
            'net_amount' => 1000,
        ]);
        app(OperationalAccountingBridge::class)->sync($invoice);

        return $invoice;
    }
}
