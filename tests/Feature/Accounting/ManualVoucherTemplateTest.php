<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountingVoucher;
use App\Models\Invoice;
use App\Models\Party;
use App\Services\Accounting\EntityAccountLinker;
use App\Services\Accounting\OperationalAccountingBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class ManualVoucherTemplateTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    private const PERMISSIONS = ['view accounting_vouchers', 'create accounting_vouchers', 'edit accounting_vouchers'];

    public function test_create_page_offers_templates_with_default_ledgers(): void
    {
        $context = $this->accountingContext(self::PERMISSIONS);

        $this->actingAs($context['user'])
            ->get(route('accounting.vouchers.create', ['template' => 'diesel']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/Vouchers/Create')
                ->where('activeTemplate', 'diesel')
                ->has('templates', 9)
                ->where('templates.1.key', 'diesel')
                ->where('templates.1.voucher_type', 'payment')
                ->where('templates.1.lines.0.account_id', $this->systemAccountId($context['company'], 'diesel_expense'))
                ->where('templates.1.lines.1.account_id', $this->systemAccountId($context['company'], 'cash')));
    }

    public function test_template_voucher_posts_with_manual_event_key(): void
    {
        $context = $this->accountingContext(self::PERMISSIONS);

        $this->actingAs($context['user'])
            ->post(route('accounting.vouchers.store'), [
                'voucher_type' => 'payment',
                'date' => now()->toDateString(),
                'narration' => 'Diesel purchase',
                'template' => 'diesel',
                'entries' => [
                    ['account_id' => $this->systemAccountId($context['company'], 'diesel_expense'), 'debit' => 2500.50],
                    ['account_id' => $this->systemAccountId($context['company'], 'cash'), 'credit' => 2500.50],
                ],
            ])
            ->assertSessionHasNoErrors();

        $voucher = AccountingVoucher::query()->sole();
        $this->assertSame('manual.diesel', $voucher->event_key);
        $this->assertNull($voucher->source_type);
        $this->assertEquals(2500.50, (float) $voucher->entries()->sum('debit'));
    }

    public function test_unbalanced_template_voucher_is_rejected(): void
    {
        $context = $this->accountingContext(self::PERMISSIONS);

        $this->actingAs($context['user'])
            ->post(route('accounting.vouchers.store'), [
                'voucher_type' => 'payment',
                'date' => now()->toDateString(),
                'template' => 'expense',
                'entries' => [
                    ['account_id' => $this->systemAccountId($context['company'], 'general_expense'), 'debit' => 100.01],
                    ['account_id' => $this->systemAccountId($context['company'], 'cash'), 'credit' => 100],
                ],
            ])
            ->assertSessionHasErrors();

        $this->assertSame(0, AccountingVoucher::query()->count());
    }

    public function test_unknown_template_is_rejected(): void
    {
        $context = $this->accountingContext(self::PERMISSIONS);

        $this->actingAs($context['user'])
            ->post(route('accounting.vouchers.store'), [
                'voucher_type' => 'journal',
                'date' => now()->toDateString(),
                'template' => 'salary',
                'entries' => [
                    ['account_id' => $this->systemAccountId($context['company'], 'general_expense'), 'debit' => 100],
                    ['account_id' => $this->systemAccountId($context['company'], 'cash'), 'credit' => 100],
                ],
            ])
            ->assertSessionHasErrors('template');
    }

    public function test_system_voucher_cannot_be_cancelled_or_reversed_manually(): void
    {
        $context = $this->accountingContext(self::PERMISSIONS);
        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Template Party',
            'address' => 'Road',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9000000002',
            'consider_for_accounting' => true,
        ]);
        app(EntityAccountLinker::class)->enable($party);

        $invoice = Invoice::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => 'INV-T1',
            'date' => now()->toDateString(),
            'party_id' => $party->id,
            'status' => 'Pending',
            'total_amount' => 1000,
            'net_amount' => 1000,
        ]);
        app(OperationalAccountingBridge::class)->sync($invoice);
        $voucher = AccountingVoucher::query()->sole();

        $this->actingAs($context['user'])
            ->post(route('accounting.vouchers.cancel', $voucher))
            ->assertSessionHasErrors('voucher');
        $this->actingAs($context['user'])
            ->post(route('accounting.vouchers.reverse', $voucher))
            ->assertSessionHasErrors('voucher');

        $this->assertTrue($voucher->fresh()->isOpenForReversal());
    }
}
