<?php

namespace Tests\Feature\Ops;

use App\Models\BankAccount;
use App\Models\DeliveryReceipt;
use App\Models\Good;
use App\Models\InwardRegister;
use App\Models\InwardRegisterDetail;
use App\Models\Party;
use App\Services\Accounting\OperationalAccountingBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class DeliveryReceiptCrudTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    private const ALL_PERMISSIONS = [
        'view delivery_receipts',
        'create delivery_receipts',
        'edit delivery_receipts',
        'delete delivery_receipts',
    ];

    public function test_index_lists_receipts_with_default_filters(): void
    {
        $context = $this->receiptContext(self::ALL_PERMISSIONS);
        $older = $this->makeReceipt($context, 'DR-OLD-1', 'GR-A');
        $latest = $this->makeReceipt($context, 'DR-NEW-2', 'GR-B');
        $this->makeReceipt($context, 'DR-LAST-YEAR', 'GR-C', ['date' => now()->subYear()->toDateString()]);

        $this->actingAs($context['user'])
            ->get(route('delivery-receipts.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('DeliveryReceipts/Index')
                ->has('receipts.data', 2)
                ->where('receipts.data.0.id', $latest->id)
                ->where('receipts.data.1.id', $older->id)
                ->has('parties', 2)
                ->where('filters.search', '')
                ->where('filters.from_date', now()->startOfMonth()->toDateString())
                ->where('filters.to_date', now()->endOfMonth()->toDateString())
                ->where('filters.consignee_id', '')
            );
    }

    public function test_index_filters_by_search_consignee_and_dates(): void
    {
        $context = $this->receiptContext(self::ALL_PERMISSIONS);
        $other = $this->makeParty($context, 'Other Consignee');
        $this->makeReceipt($context, 'DR-FIND-1', 'GR-100');
        $this->makeReceipt($context, 'DR-OTHER-2', 'GR-FIND-200', ['consignee_id' => $other->id]);
        $this->makeReceipt($context, 'DR-SKIP-3', 'GR-300');
        $this->makeReceipt($context, 'DR-FIND-OLD', 'GR-400', ['date' => now()->subMonths(3)->toDateString()]);

        $this->actingAs($context['user'])
            ->get(route('delivery-receipts.index', ['search' => 'FIND']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('receipts.data', 2));

        $this->actingAs($context['user'])
            ->get(route('delivery-receipts.index', ['search' => 'FIND', 'consignee_id' => $other->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('receipts.data', 1)
                ->where('receipts.data.0.delivery_receipt_no', 'DR-OTHER-2')
                ->where('filters.consignee_id', (string) $other->id)
            );

        $this->actingAs($context['user'])
            ->get(route('delivery-receipts.index', [
                'from_date' => now()->subMonths(4)->toDateString(),
                'to_date' => now()->subMonths(2)->toDateString(),
            ]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('receipts.data', 1)
                ->where('receipts.data.0.delivery_receipt_no', 'DR-FIND-OLD')
            );
    }

    public function test_index_validates_filters_and_requires_permission(): void
    {
        $context = $this->receiptContext(['view delivery_receipts']);

        $this->actingAs($context['user'])
            ->from(route('delivery-receipts.index'))
            ->get(route('delivery-receipts.index', ['from_date' => '2026-02-10', 'to_date' => '2026-02-01']))
            ->assertSessionHasErrors('to_date');

        $this->actingAs($context['user'])
            ->from(route('delivery-receipts.index'))
            ->get(route('delivery-receipts.index', ['consignee_id' => 999999]))
            ->assertSessionHasErrors('consignee_id');

        $noPermission = $this->receiptContext([], 'B');
        $this->actingAs($noPermission['user'])->get(route('delivery-receipts.index'))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('delivery-receipts.index'))->assertRedirect(route('login'));
        $this->post(route('delivery-receipts.store'), [])->assertRedirect(route('login'));
    }

    public function test_create_page_excludes_gr_numbers_already_used_and_exposes_gst_defaults(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);
        $context['branch']->update(['has_gst' => true, 'gst_slab' => 18, 'gst_exemption_limit' => 250]);
        $used = $this->makeInward($context, ['gr_no' => 'GR-USED']);
        $generated = $this->makeInward($context, ['gr_no' => null]);
        $this->makeInward($context, ['gr_no' => 'GR-DISPATCH', 'disposition' => 'dispatch']);
        $this->makeInward($context, ['gr_no' => 'GR-DONE', 'status' => 'delivered']);
        $this->makeReceipt($context, 'DR-USED', $used->gr_no);

        $this->actingAs($context['user'])
            ->get(route('delivery-receipts.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('DeliveryReceipts/Create')
                ->has('gr_sources', 1)
                ->where('gr_sources.0.gr_no', 'GR-'.$generated->id)
                ->has('parties', 2)
                ->has('bankAccounts')
                ->where('gst_rate', 18)
                ->where('gst_exemption_limit', 250)
                ->has('next_delivery_receipt_no')
            );
    }

    public function test_create_page_has_zero_gst_when_branch_has_no_gst(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);

        $this->actingAs($context['user'])
            ->get(route('delivery-receipts.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('gst_rate', 0)
                ->where('gst_exemption_limit', 0)
            );
    }

    public function test_create_page_requires_permission(): void
    {
        $context = $this->receiptContext(['view delivery_receipts']);

        $this->actingAs($context['user'])->get(route('delivery-receipts.create'))->assertForbidden();
    }

    public function test_gr_lookup_rejects_invalid_reference_and_missing_key(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);

        $this->actingAs($context['user'])
            ->getJson(route('delivery-receipts.gr-lookup', ['key' => 'bilty:1']))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid GR reference.');

        $this->actingAs($context['user'])
            ->getJson(route('delivery-receipts.gr-lookup'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('key');
    }

    public function test_gr_lookup_returns_not_found_for_missing_or_undeliverable_entries(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);
        $dispatch = $this->makeInward($context, ['gr_no' => 'GR-DISPATCH', 'disposition' => 'dispatch']);

        $this->actingAs($context['user'])
            ->getJson(route('delivery-receipts.gr-lookup', ['key' => 'inward:999999']))
            ->assertNotFound()
            ->assertJsonPath('message', 'GR entry not found or not available for delivery.');

        $this->actingAs($context['user'])
            ->getJson(route('delivery-receipts.gr-lookup', ['key' => 'inward:'.$dispatch->id]))
            ->assertNotFound();
    }

    public function test_gr_lookup_falls_back_to_generated_gr_number_and_maps_charges(): void
    {
        $context = $this->receiptContext(['edit delivery_receipts']);
        $context['branch']->update(['has_gst' => true, 'gst_slab' => 12]);
        $inward = $this->makeInward($context, [
            'gr_no' => null,
            'private_marka' => 'MARKA-1',
            'freight' => 700,
            'home_delivery_charge' => 40,
            'no_of_packages' => 8,
            'weight' => 90,
        ]);

        $this->actingAs($context['user'])
            ->getJson(route('delivery-receipts.gr-lookup', ['key' => 'inward:'.$inward->id]))
            ->assertOk()
            ->assertJsonPath('gr_no_bilty_no', 'GR-'.$inward->id)
            ->assertJsonPath('haste', 'MARKA-1')
            ->assertJsonPath('freight', 700)
            ->assertJsonPath('delivery_charge', 40)
            ->assertJsonPath('packages', 8)
            ->assertJsonPath('weight', 90)
            ->assertJsonPath('gst_rate', 12)
            ->assertJsonPath('item_name', 'Goods A')
            ->assertJsonPath('to_station', $context['toStation']->name);
    }

    public function test_gr_lookup_requires_create_or_edit_permission(): void
    {
        $context = $this->receiptContext(['view delivery_receipts']);
        $inward = $this->makeInward($context);

        $this->actingAs($context['user'])
            ->getJson(route('delivery-receipts.gr-lookup', ['key' => 'inward:'.$inward->id]))
            ->assertForbidden();
    }

    public function test_store_in_cash_mode_clears_bank_and_due_party_and_recomputes_totals(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);
        $bank = $this->makeBankAccount($context);
        $inward = $this->makeInward($context);

        $this->actingAs($context['user'])
            ->post(route('delivery-receipts.store'), $this->validPayload($context, [
                'gr_no_bilty_no' => $inward->gr_no,
                'mode_of_payment' => 'Cash',
                'bank_account_id' => $bank->id,
                'payment_due_from_id' => $context['consignee']->id,
                'freight' => 1000,
                'handling_charge' => 50,
                'less' => 25,
                'final_amount' => 1,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('delivery-receipts.index'))
            ->assertSessionHas('success', 'Delivery Receipt created successfully.');

        $receipt = DeliveryReceipt::query()->firstOrFail();
        $this->assertSame($context['company']->id, $receipt->company_id);
        $this->assertSame($context['branch']->id, $receipt->branch_id);
        $this->assertNull($receipt->bank_account_id);
        $this->assertNull($receipt->payment_due_from_id);
        $this->assertEqualsWithDelta(1050.0, (float) $receipt->total_amount, 0.001);
        $this->assertEqualsWithDelta(1025.0, (float) $receipt->final_amount, 0.001);
    }

    public function test_store_in_bank_mode_requires_and_keeps_bank_account(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);
        $bank = $this->makeBankAccount($context);

        $this->actingAs($context['user'])
            ->post(route('delivery-receipts.store'), $this->validPayload($context, ['mode_of_payment' => 'Bank']))
            ->assertSessionHasErrors('bank_account_id');
        $this->assertDatabaseCount('delivery_receipts', 0);

        $this->actingAs($context['user'])
            ->post(route('delivery-receipts.store'), $this->validPayload($context, [
                'mode_of_payment' => 'Bank',
                'bank_account_id' => $bank->id,
            ]))
            ->assertSessionHasNoErrors();

        $receipt = DeliveryReceipt::query()->firstOrFail();
        $this->assertSame($bank->id, $receipt->bank_account_id);
        $this->assertNull($receipt->payment_due_from_id);
    }

    public function test_store_rejects_bank_account_from_another_company(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);
        $other = $this->receiptContext([], 'B');
        $foreignBank = $this->makeBankAccount($other);

        $this->actingAs($context['user'])
            ->post(route('delivery-receipts.store'), $this->validPayload($context, [
                'mode_of_payment' => 'Bank',
                'bank_account_id' => $foreignBank->id,
            ]))
            ->assertSessionHasErrors('bank_account_id');

        $this->assertDatabaseCount('delivery_receipts', 0);
    }

    public function test_store_in_due_mode_keeps_payment_due_party_and_clears_bank(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);
        $bank = $this->makeBankAccount($context);
        $payer = $this->makeParty($context, 'Due Payer');

        $this->actingAs($context['user'])
            ->post(route('delivery-receipts.store'), $this->validPayload($context, [
                'mode_of_payment' => 'Due',
                'payment_due_from_id' => $payer->id,
                'bank_account_id' => $bank->id,
            ]))
            ->assertSessionHasNoErrors();

        $receipt = DeliveryReceipt::query()->firstOrFail();
        $this->assertSame($payer->id, $receipt->payment_due_from_id);
        $this->assertNull($receipt->bank_account_id);
    }

    public function test_store_flashes_print_url_when_print_after_save_requested(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);

        $this->actingAs($context['user'])
            ->post(route('delivery-receipts.store'), $this->validPayload($context, ['print_after_save' => true]))
            ->assertSessionHasNoErrors();

        $receipt = DeliveryReceipt::query()->firstOrFail();
        $this->assertSame(route('delivery-receipts.print', $receipt), session('print_url'));
    }

    public function test_store_calculates_gst_paid_by_transporter_above_exemption_limit(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);
        $context['branch']->update(['has_gst' => true, 'gst_slab' => 10, 'gst_exemption_limit' => 100]);

        $this->actingAs($context['user'])
            ->post(route('delivery-receipts.store'), $this->validPayload($context, [
                'freight' => 1000,
                'gst_on' => 'total_charge',
                'gst_pay_by' => 'Transporter',
            ]))
            ->assertSessionHasNoErrors();

        $receipt = DeliveryReceipt::query()->firstOrFail();
        $this->assertEqualsWithDelta(100.0, (float) $receipt->gst, 0.001);
        $this->assertEqualsWithDelta(1100.0, (float) $receipt->final_amount, 0.001);
        $this->assertEqualsWithDelta(10.0, (float) $receipt->gst_rate, 0.001);
    }

    public function test_store_applies_no_gst_when_exempted_or_below_exemption_limit(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);
        $context['branch']->update(['has_gst' => true, 'gst_slab' => 10, 'gst_exemption_limit' => 500]);

        $this->actingAs($context['user'])
            ->post(route('delivery-receipts.store'), $this->validPayload($context, [
                'delivery_receipt_no' => 'DR-EX-1',
                'freight' => 1000,
                'gst_pay_by' => 'Exempted',
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($context['user'])
            ->post(route('delivery-receipts.store'), $this->validPayload($context, [
                'delivery_receipt_no' => 'DR-EX-2',
                'freight' => 400,
                'gst_pay_by' => 'Transporter',
            ]))
            ->assertSessionHasNoErrors();

        $exempted = DeliveryReceipt::query()->where('delivery_receipt_no', 'DR-EX-1')->firstOrFail();
        $this->assertEqualsWithDelta(0.0, (float) $exempted->gst, 0.001);
        $this->assertEqualsWithDelta(1000.0, (float) $exempted->final_amount, 0.001);

        $belowLimit = DeliveryReceipt::query()->where('delivery_receipt_no', 'DR-EX-2')->firstOrFail();
        $this->assertEqualsWithDelta(0.0, (float) $belowLimit->gst, 0.001);
    }

    public function test_store_gst_on_delivery_charge_excludes_freight_from_base(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);
        $context['branch']->update(['has_gst' => true, 'gst_slab' => 10, 'gst_exemption_limit' => 0]);

        $this->actingAs($context['user'])
            ->post(route('delivery-receipts.store'), $this->validPayload($context, [
                'freight' => 1000,
                'delivery_charge' => 200,
                'gst_on' => 'delivery_charge',
                'gst_pay_by' => 'Consignee',
            ]))
            ->assertSessionHasNoErrors();

        $receipt = DeliveryReceipt::query()->firstOrFail();
        $this->assertEqualsWithDelta(20.0, (float) $receipt->gst, 0.001);
        $this->assertEqualsWithDelta(1200.0, (float) $receipt->final_amount, 0.001);
    }

    public function test_store_validates_required_fields_and_enums(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);

        $this->actingAs($context['user'])
            ->from(route('delivery-receipts.create'))
            ->post(route('delivery-receipts.store'), [])
            ->assertRedirect(route('delivery-receipts.create'))
            ->assertSessionHasErrors([
                'delivery_receipt_no',
                'date',
                'gr_no_bilty_no',
                'mode_of_payment',
                'consignee_id',
                'final_amount',
            ]);

        $this->actingAs($context['user'])
            ->post(route('delivery-receipts.store'), $this->validPayload($context, [
                'mode_of_payment' => 'Barter',
                'gst_on' => 'everything',
                'gst_pay_by' => 'Nobody',
                'consignee_id' => 999999,
                'freight' => -1,
                'date' => 'not-a-date',
            ]))
            ->assertSessionHasErrors(['mode_of_payment', 'gst_on', 'gst_pay_by', 'consignee_id', 'freight', 'date']);

        $this->assertDatabaseCount('delivery_receipts', 0);
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->receiptContext(['view delivery_receipts']);

        $this->actingAs($context['user'])
            ->post(route('delivery-receipts.store'), $this->validPayload($context))
            ->assertForbidden();

        $this->assertDatabaseCount('delivery_receipts', 0);
    }

    public function test_show_resolves_gr_source_from_inward_register_by_gr_number(): void
    {
        $context = $this->receiptContext(['view delivery_receipts']);
        $inward = $this->makeInward($context, [
            'gr_no' => 'GR-SHOW-1',
            'private_marka' => 'MK',
            'no_of_packages' => 6,
            'weight' => 66,
            'freight' => 600,
        ]);
        $receipt = $this->makeReceipt($context, 'DR-SHOW-1', $inward->gr_no);

        $this->actingAs($context['user'])
            ->get(route('delivery-receipts.show', $receipt))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('DeliveryReceipts/Show')
                ->where('receipt.id', $receipt->id)
                ->where('receipt.consignee.name', 'Consignee A')
                ->where('gr_source.consignor', 'Consignor A')
                ->where('gr_source.from_station', $context['fromStation']->name)
                ->where('gr_source.to_station', $context['toStation']->name)
                ->where('gr_source.goods_name', 'Goods A')
                ->where('gr_source.private_marka', 'MK')
                ->where('gr_source.packages', 6)
                ->where('gr_source.disposition', 'delivery')
                ->where('gr_source.status', 'pending')
                ->where('gr_source.received_date', now()->toDateString())
                ->has('accounting')
            );
    }

    public function test_show_resolves_gr_source_from_generated_gr_number(): void
    {
        $context = $this->receiptContext(['view delivery_receipts']);
        $inward = $this->makeInward($context, ['gr_no' => null]);
        $receipt = $this->makeReceipt($context, 'DR-SHOW-2', 'GR-'.$inward->id);

        $this->actingAs($context['user'])
            ->get(route('delivery-receipts.show', $receipt))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('gr_source.consignor', 'Consignor A')
            );
    }

    public function test_show_returns_empty_gr_source_when_gr_is_unknown(): void
    {
        $context = $this->receiptContext(['view delivery_receipts']);
        $receipt = $this->makeReceipt($context, 'DR-SHOW-3', 'UNKNOWN-GR');

        $this->actingAs($context['user'])
            ->get(route('delivery-receipts.show', $receipt))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('gr_source.consignor', null)
                ->where('gr_source.packages', null)
                ->where('gr_source.status', null)
            );
    }

    public function test_show_hides_other_tenants_receipts(): void
    {
        $context = $this->receiptContext(['view delivery_receipts']);
        $other = $this->receiptContext([], 'B');
        $foreign = $this->makeReceipt($other, 'DR-FOREIGN', 'GR-F');

        $this->actingAs($context['user'])
            ->get(route('delivery-receipts.show', $foreign))
            ->assertNotFound();
    }

    public function test_edit_page_renders_receipt_and_keeps_its_own_gr_selectable(): void
    {
        $context = $this->receiptContext(['edit delivery_receipts']);
        $context['branch']->update(['has_gst' => true, 'gst_slab' => 12, 'gst_exemption_limit' => 50]);
        $own = $this->makeInward($context, ['gr_no' => 'GR-OWN']);
        $taken = $this->makeInward($context, ['gr_no' => 'GR-TAKEN']);
        $this->makeInward($context, ['gr_no' => 'GR-FREE']);
        $receipt = $this->makeReceipt($context, 'DR-EDIT-1', $own->gr_no);
        $this->makeReceipt($context, 'DR-EDIT-2', $taken->gr_no);

        $this->actingAs($context['user'])
            ->get(route('delivery-receipts.edit', $receipt))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('DeliveryReceipts/Edit')
                ->where('receipt.id', $receipt->id)
                ->where('receipt.consignee.name', 'Consignee A')
                ->has('parties')
                ->has('bankAccounts')
                ->has('gr_sources', 2)
                ->where('gst_rate', 12)
                ->where('gst_exemption_limit', 50)
            );
    }

    public function test_edit_page_requires_permission(): void
    {
        $context = $this->receiptContext(['view delivery_receipts']);
        $receipt = $this->makeReceipt($context, 'DR-EDIT-3', 'GR-X');

        $this->actingAs($context['user'])->get(route('delivery-receipts.edit', $receipt))->assertForbidden();
    }

    public function test_update_switches_payment_mode_and_normalizes_related_ids(): void
    {
        $context = $this->receiptContext(['edit delivery_receipts']);
        $bank = $this->makeBankAccount($context);
        $payer = $this->makeParty($context, 'Due Payer');
        $receipt = $this->makeReceipt($context, 'DR-UPD-1', 'GR-U1', [
            'mode_of_payment' => 'Bank',
            'bank_account_id' => $bank->id,
        ]);

        $this->actingAs($context['user'])
            ->put(route('delivery-receipts.update', $receipt), $this->validPayload($context, [
                'delivery_receipt_no' => 'DR-UPD-1',
                'gr_no_bilty_no' => 'GR-U1',
                'mode_of_payment' => 'Due',
                'payment_due_from_id' => $payer->id,
                'bank_account_id' => $bank->id,
                'freight' => 300,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('delivery-receipts.index'))
            ->assertSessionHas('success', 'Delivery Receipt updated successfully.');

        $receipt->refresh();
        $this->assertSame('Due', $receipt->mode_of_payment);
        $this->assertSame($payer->id, $receipt->payment_due_from_id);
        $this->assertNull($receipt->bank_account_id);
        $this->assertEqualsWithDelta(300.0, (float) $receipt->final_amount, 0.001);
    }

    public function test_update_to_bank_mode_stores_bank_and_clears_due_party(): void
    {
        $context = $this->receiptContext(['edit delivery_receipts']);
        $bank = $this->makeBankAccount($context);
        $payer = $this->makeParty($context, 'Due Payer');
        $receipt = $this->makeReceipt($context, 'DR-UPD-2', 'GR-U2', [
            'mode_of_payment' => 'Due',
            'payment_due_from_id' => $payer->id,
        ]);

        $this->actingAs($context['user'])
            ->put(route('delivery-receipts.update', $receipt), $this->validPayload($context, [
                'delivery_receipt_no' => 'DR-UPD-2',
                'mode_of_payment' => 'Bank',
                'bank_account_id' => $bank->id,
                'payment_due_from_id' => $payer->id,
            ]))
            ->assertSessionHasNoErrors();

        $receipt->refresh();
        $this->assertSame($bank->id, $receipt->bank_account_id);
        $this->assertNull($receipt->payment_due_from_id);
    }

    public function test_update_with_print_after_save_flashes_print_url(): void
    {
        $context = $this->receiptContext(['edit delivery_receipts']);
        $receipt = $this->makeReceipt($context, 'DR-UPD-3', 'GR-U3');

        $this->actingAs($context['user'])
            ->put(route('delivery-receipts.update', $receipt), $this->validPayload($context, [
                'delivery_receipt_no' => 'DR-UPD-3',
                'print_after_save' => '1',
            ]))
            ->assertRedirect(route('delivery-receipts.index'))
            ->assertSessionHas('print_url', route('delivery-receipts.print', $receipt));
    }

    public function test_update_validates_input_and_requires_permission(): void
    {
        $context = $this->receiptContext(['edit delivery_receipts']);
        $receipt = $this->makeReceipt($context, 'DR-UPD-4', 'GR-U4');

        $this->actingAs($context['user'])
            ->put(route('delivery-receipts.update', $receipt), [])
            ->assertSessionHasErrors(['delivery_receipt_no', 'mode_of_payment', 'consignee_id', 'final_amount']);

        $this->actingAs($context['user'])
            ->put(route('delivery-receipts.update', $receipt), $this->validPayload($context, ['mode_of_payment' => 'Due']))
            ->assertSessionHasErrors('payment_due_from_id');

        $viewer = $this->receiptContext(['view delivery_receipts'], 'B');
        $viewerReceipt = $this->makeReceipt($viewer, 'DR-UPD-5', 'GR-U5');
        $this->actingAs($viewer['user'])
            ->put(route('delivery-receipts.update', $viewerReceipt), $this->validPayload($viewer))
            ->assertForbidden();
    }

    public function test_update_cannot_touch_other_tenants_receipt(): void
    {
        $context = $this->receiptContext(['edit delivery_receipts']);
        $other = $this->receiptContext([], 'B');
        $foreign = $this->makeReceipt($other, 'DR-FOREIGN-2', 'GR-F2');

        $this->actingAs($context['user'])
            ->put(route('delivery-receipts.update', $foreign), $this->validPayload($context))
            ->assertNotFound();
    }

    public function test_destroy_soft_deletes_with_remark_and_reverses_voucher(): void
    {
        $context = $this->receiptContext(['delete delivery_receipts', 'create delivery_receipts']);

        $this->actingAs($context['user'])
            ->post(route('delivery-receipts.store'), $this->validPayload($context, [
                'delivery_receipt_no' => 'DR-DEL-1',
                'freight' => 500,
            ]))
            ->assertSessionHasNoErrors();

        $receipt = DeliveryReceipt::query()->where('delivery_receipt_no', 'DR-DEL-1')->firstOrFail();
        $bridge = app(OperationalAccountingBridge::class);
        $this->assertGreaterThan(0, $bridge->activeVouchers($receipt)->count());

        $this->actingAs($context['user'])
            ->delete(route('delivery-receipts.destroy', $receipt), ['delete_remark' => 'Entered twice'])
            ->assertRedirect(route('delivery-receipts.index'))
            ->assertSessionHas('success', 'Delivery Receipt deleted successfully.');

        $this->assertSoftDeleted('delivery_receipts', ['id' => $receipt->id]);
        $this->assertDatabaseHas('delivery_receipts', [
            'id' => $receipt->id,
            'delete_remark' => 'Entered twice',
            'deleted_by' => $context['user']->id,
        ]);
        $this->assertCount(0, $bridge->activeVouchers($receipt));
    }

    public function test_destroy_without_remark_still_deletes(): void
    {
        $context = $this->receiptContext(['delete delivery_receipts']);
        $receipt = $this->makeReceipt($context, 'DR-DEL-2', 'GR-D2');

        $this->actingAs($context['user'])
            ->delete(route('delivery-receipts.destroy', $receipt))
            ->assertRedirect(route('delivery-receipts.index'));

        $this->assertSoftDeleted('delivery_receipts', ['id' => $receipt->id]);
    }

    public function test_destroy_requires_permission_and_respects_tenancy(): void
    {
        $context = $this->receiptContext(['view delivery_receipts', 'edit delivery_receipts']);
        $receipt = $this->makeReceipt($context, 'DR-DEL-3', 'GR-D3');

        $this->actingAs($context['user'])
            ->delete(route('delivery-receipts.destroy', $receipt))
            ->assertForbidden();
        $this->assertNotSoftDeleted('delivery_receipts', ['id' => $receipt->id]);

        $other = $this->receiptContext(['delete delivery_receipts'], 'B');
        $this->actingAs($other['user'])
            ->delete(route('delivery-receipts.destroy', $receipt))
            ->assertNotFound();
        $this->assertNotSoftDeleted('delivery_receipts', ['id' => $receipt->id]);
    }

    public function test_print_renders_view_with_formatted_gr_source(): void
    {
        $context = $this->receiptContext(['view delivery_receipts']);
        $this->makeInward($context, [
            'gr_no' => 'GR-PRINT-1',
            'private_marka' => 'PM-9',
            'basis_of_booking' => 'To Pay',
        ]);
        $receipt = $this->makeReceipt($context, 'DR-PRN-1', 'GR-PRINT-1');

        $this->actingAs($context['user'])
            ->get(route('delivery-receipts.print', $receipt))
            ->assertOk()
            ->assertViewIs('prints.delivery_receipt')
            ->assertViewHas('receipt', fn (DeliveryReceipt $viewReceipt) => $viewReceipt->is($receipt))
            ->assertViewHas('company', fn ($company) => $company->id === $context['company']->id)
            ->assertViewHas('branch', fn ($branch) => $branch->id === $context['branch']->id)
            ->assertViewHas('grSource', fn (array $source) => $source['consignor'] === 'Consignor A'
                && $source['private_marka'] === 'PM-9'
                && $source['basis_of_booking'] === 'To Pay'
                && $source['gr_date'] === now()->format('d/m/Y')
                && $source['received_date'] === now()->format('d/m/Y'))
            ->assertSee('DR-PRN-1', false);
    }

    public function test_print_with_unknown_gr_uses_empty_source(): void
    {
        $context = $this->receiptContext(['view delivery_receipts']);
        $receipt = $this->makeReceipt($context, 'DR-PRN-2', 'NO-SUCH-GR');

        $this->actingAs($context['user'])
            ->get(route('delivery-receipts.print', $receipt))
            ->assertOk()
            ->assertViewHas('grSource', fn (array $source) => $source['consignor'] === null
                && $source['gr_date'] === null
                && $source['received_date'] === null);
    }

    public function test_print_requires_permission_and_hides_other_tenants(): void
    {
        $context = $this->receiptContext(['create delivery_receipts']);
        $receipt = $this->makeReceipt($context, 'DR-PRN-3', 'GR-P3');

        $this->actingAs($context['user'])->get(route('delivery-receipts.print', $receipt))->assertForbidden();

        $other = $this->receiptContext(['view delivery_receipts'], 'B');
        $this->actingAs($other['user'])->get(route('delivery-receipts.print', $receipt))->assertNotFound();
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    private function receiptContext(array $permissions, string $suffix = 'A'): array
    {
        $context = $this->accountingContext($permissions, $suffix);

        $context['consignor'] = $this->makeParty($context, "Consignor {$suffix}");
        $context['consignee'] = $this->makeParty($context, "Consignee {$suffix}");
        $context['good'] = Good::create(['owner_id' => $context['owner']->id, 'name' => "Goods {$suffix}"]);
        $context['register'] = InwardRegister::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
        ]);

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeParty(array $context, string $name): Party
    {
        return Party::create([
            'company_id' => $context['company']->id,
            'name' => $name,
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeBankAccount(array $context): BankAccount
    {
        return BankAccount::create([
            'company_id' => $context['company']->id,
            'name' => 'Main Bank',
            'bank_name' => 'SBI',
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $attributes
     */
    private function makeInward(array $context, array $attributes = []): InwardRegisterDetail
    {
        static $sequence = 0;
        $sequence++;

        return InwardRegisterDetail::create(array_merge([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'inward_register_id' => $context['register']->id,
            'gr_no' => 'GR-AUTO-'.$sequence,
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'consignor_id' => $context['consignor']->id,
            'consignee_id' => $context['consignee']->id,
            'good_id' => $context['good']->id,
            'no_of_packages' => 5,
            'weight' => 50,
            'freight' => 500,
            'home_delivery_charge' => 0,
            'disposition' => 'delivery',
            'status' => 'pending',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $attributes
     */
    private function makeReceipt(array $context, string $number, string $grNo, array $attributes = []): DeliveryReceipt
    {
        return DeliveryReceipt::create(array_merge([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'delivery_receipt_no' => $number,
            'date' => now()->toDateString(),
            'gr_no_bilty_no' => $grNo,
            'mode_of_payment' => 'Cash',
            'consignee_id' => $context['consignee']->id,
            'freight' => 500,
            'total_amount' => 500,
            'final_amount' => 500,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $context, array $overrides = []): array
    {
        return array_merge([
            'delivery_receipt_no' => 'DR-NEW-1',
            'date' => now()->toDateString(),
            'gr_no_bilty_no' => 'GR-NEW-1',
            'mode_of_payment' => 'Cash',
            'consignee_id' => $context['consignee']->id,
            'freight' => 500,
            'final_amount' => 500,
        ], $overrides);
    }
}
