<?php

namespace Tests\Feature\Ops;

use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\BankAccount;
use App\Models\Bilty;
use App\Models\BiltyDetail;
use App\Models\BiltyEwayBill;
use App\Models\Good;
use App\Models\PackageType;
use App\Models\Party;
use App\Services\Accounting\OperationalAccountingBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class BiltyCrudTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    private const ALL_PERMISSIONS = [
        'view bilties',
        'create bilties',
        'edit bilties',
        'delete bilties',
    ];

    public function test_index_lists_bilties_with_default_month_filters(): void
    {
        $context = $this->biltyContext(self::ALL_PERMISSIONS);
        $older = $this->makeBilty($context, 'BLT-OLD-1');
        $latest = $this->makeBilty($context, 'BLT-NEW-2');
        $this->makeBilty($context, 'BLT-LAST-YEAR', ['date' => now()->subYear()->toDateString()]);

        $this->actingAs($context['user'])
            ->get(route('bilties.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Bilties/Index')
                ->has('bilties.data', 2)
                ->where('bilties.data.0.id', $latest->id)
                ->where('bilties.data.1.id', $older->id)
                ->where('filters.search', '')
                ->where('filters.from_date', now()->startOfMonth()->toDateString())
                ->where('filters.to_date', now()->endOfMonth()->toDateString())
                ->where('filters.consignor_id', '')
                ->where('filters.consignee_id', '')
                ->has('parties', 2)
            );
    }

    public function test_index_filters_by_search_consignor_consignee_and_dates(): void
    {
        $context = $this->biltyContext(self::ALL_PERMISSIONS);
        $this->makeBilty($context, 'FIND-1');
        $otherParty = $this->makeParty($context, 'Other Party');
        $this->makeBilty($context, 'FIND-2', ['consignor_id' => $otherParty->id]);
        $this->makeBilty($context, 'FIND-3', ['consignee_id' => $otherParty->id]);
        $this->makeBilty($context, 'SKIP-4');
        $this->makeBilty($context, 'FIND-OLD', ['date' => now()->subMonths(3)->toDateString()]);

        $this->actingAs($context['user'])
            ->get(route('bilties.index', ['search' => 'FIND']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('bilties.data', 3));

        $this->actingAs($context['user'])
            ->get(route('bilties.index', ['search' => 'FIND', 'consignor_id' => $otherParty->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('bilties.data', 1)
                ->where('bilties.data.0.bilty_number', 'FIND-2')
                ->where('filters.consignor_id', (string) $otherParty->id)
            );

        $this->actingAs($context['user'])
            ->get(route('bilties.index', ['consignee_id' => $otherParty->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('bilties.data', 1)
                ->where('bilties.data.0.bilty_number', 'FIND-3')
                ->where('filters.consignee_id', (string) $otherParty->id)
            );

        $this->actingAs($context['user'])
            ->get(route('bilties.index', [
                'from_date' => now()->subMonths(4)->toDateString(),
                'to_date' => now()->subMonths(2)->toDateString(),
            ]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('bilties.data', 1)
                ->where('bilties.data.0.bilty_number', 'FIND-OLD')
            );
    }

    public function test_index_validates_filters(): void
    {
        $context = $this->biltyContext(['view bilties']);

        $this->actingAs($context['user'])
            ->from(route('bilties.index'))
            ->get(route('bilties.index', ['from_date' => '2026-02-10', 'to_date' => '2026-02-01']))
            ->assertSessionHasErrors('to_date');

        $this->actingAs($context['user'])
            ->from(route('bilties.index'))
            ->get(route('bilties.index', ['consignor_id' => 999999, 'consignee_id' => 'abc']))
            ->assertSessionHasErrors(['consignor_id', 'consignee_id']);
    }

    public function test_index_requires_permission_and_authentication(): void
    {
        $context = $this->biltyContext([]);

        $this->actingAs($context['user'])->get(route('bilties.index'))->assertForbidden();

        auth()->logout();
        $this->get(route('bilties.index'))->assertRedirect(route('login'));
    }

    public function test_create_page_renders_lookup_data(): void
    {
        $context = $this->biltyContext(['create bilties']);
        $this->makeBankAccount($context);

        $this->actingAs($context['user'])
            ->get(route('bilties.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Bilties/Create')
                ->has('parties', 2)
                ->has('stations')
                ->has('goods', 1)
                ->has('package_types', 1)
                ->has('bankAccounts', 1)
                ->has('cartageAccounts')
                ->has('agents')
                ->has('next_bilty_number')
                ->where('default_gst_percent', 0)
                ->where('gst_exemption_limit', 0)
                ->where('branch_station_id', $context['fromStation']->id)
            );
    }

    public function test_create_page_exposes_gst_defaults_when_branch_has_gst(): void
    {
        $context = $this->biltyContext(['create bilties']);
        $context['branch']->update(['has_gst' => true, 'gst_slab' => 12, 'gst_exemption_limit' => 750]);

        $this->actingAs($context['user'])
            ->get(route('bilties.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('default_gst_percent', 12)
                ->where('gst_exemption_limit', 750)
            );
    }

    public function test_create_page_requires_permission(): void
    {
        $context = $this->biltyContext(['view bilties']);

        $this->actingAs($context['user'])->get(route('bilties.create'))->assertForbidden();
    }

    public function test_store_creates_to_pay_bilty_with_details_and_server_totals(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $response = $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'bilty_number' => 'TP-100',
                'freight' => 1000,
                'bilty_charge' => 50,
                'amount' => 1,
            ]));

        $response->assertSessionHasNoErrors()->assertRedirect(route('bilties.index'));

        $bilty = Bilty::query()->where('bilty_number', 'TP-100')->firstOrFail();
        $this->assertSame($context['company']->id, $bilty->company_id);
        $this->assertSame($context['branch']->id, $bilty->branch_id);
        $this->assertSame($context['user']->id, $bilty->created_by);
        $this->assertSame('Pending', $bilty->status);
        $this->assertSame('To Pay', $bilty->payment_type);
        $this->assertEqualsWithDelta(1050.0, (float) $bilty->amount, 0.001);
        $this->assertEqualsWithDelta(1050.0, (float) $bilty->charges_total, 0.001);
        $this->assertDatabaseHas('bilty_details', [
            'bilty_id' => $bilty->id,
            'good_id' => $context['good']->id,
            'package_type_id' => $context['packageType']->id,
            'no_of_packages' => 5,
            'rate_type' => 'Fixed',
        ]);
        $this->assertSame(
            'Bilty created successfully.',
            session('success')
        );
    }

    public function test_store_flashes_print_url_when_print_after_save_requested(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, ['print_after_save' => true]))
            ->assertSessionHasNoErrors();

        $bilty = Bilty::query()->firstOrFail();
        $this->assertSame(route('bilties.print', $bilty), session('print_url'));
    }

    public function test_store_paid_bilty_with_cash_mode_posts_receipt_voucher(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'payment_type' => 'Paid',
                'payment_mode' => 'cash',
                'bank_account_id' => 12345,
                'freight' => 800,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('bilties.index'));

        $bilty = Bilty::query()->firstOrFail();
        $this->assertSame('Paid', $bilty->payment_type);
        $this->assertSame('Cash', $bilty->payment_mode);
        $this->assertNull($bilty->bank_account_id);
        $this->assertGreaterThan(0, app(OperationalAccountingBridge::class)->activeVouchers($bilty)->count());
    }

    public function test_store_paid_bilty_with_bank_mode_keeps_bank_account(): void
    {
        $context = $this->biltyContext(['create bilties']);
        $bank = $this->makeBankAccount($context);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'payment_type' => 'Paid',
                'payment_mode' => 'bank',
                'bank_account_id' => $bank->id,
                'freight' => 800,
            ]))
            ->assertSessionHasNoErrors();

        $bilty = Bilty::query()->firstOrFail();
        $this->assertSame('Bank', $bilty->payment_mode);
        $this->assertSame($bank->id, $bilty->bank_account_id);
    }

    public function test_store_paid_bilty_requires_payment_mode(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $this->actingAs($context['user'])
            ->from(route('bilties.create'))
            ->post(route('bilties.store'), $this->validPayload($context, ['payment_type' => 'Paid']))
            ->assertRedirect(route('bilties.create'))
            ->assertSessionHasErrors('payment_mode');

        $this->assertDatabaseCount('bilties', 0);
    }

    public function test_store_paid_bank_mode_requires_bank_account(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'payment_type' => 'Paid',
                'payment_mode' => 'Bank',
            ]))
            ->assertSessionHasErrors('bank_account_id');
    }

    public function test_store_rejects_bank_account_from_another_company(): void
    {
        $context = $this->biltyContext(['create bilties']);
        $other = $this->accountingContext([], 'B');
        $foreignBank = BankAccount::create(['company_id' => $other['company']->id, 'name' => 'Foreign Bank']);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'payment_type' => 'Paid',
                'payment_mode' => 'Bank',
                'bank_account_id' => $foreignBank->id,
            ]))
            ->assertSessionHasErrors('bank_account_id');

        $this->assertDatabaseCount('bilties', 0);
    }

    public function test_store_payment_mode_is_ignored_for_non_paid_bilty(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'payment_type' => 'To Pay',
                'payment_mode' => 'Bank',
            ]))
            ->assertSessionHasNoErrors();

        $bilty = Bilty::query()->firstOrFail();
        $this->assertNull($bilty->payment_mode);
        $this->assertNull($bilty->bank_account_id);
    }

    public function test_store_tbb_bilty_does_not_require_rates(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $payload = $this->validPayload($context, ['payment_type' => 'TBB']);
        $payload['details'] = [[
            'good_id' => $context['good']->id,
            'package_type_id' => $context['packageType']->id,
            'no_of_packages' => 3,
            'gross_weight' => 30,
            'charged_weight' => 30,
            'rate_type' => '',
            'rate' => '',
        ]];

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('bilties.index'));

        $bilty = Bilty::query()->firstOrFail();
        $this->assertSame('TBB', $bilty->payment_type);
        $detail = $bilty->biltyDetails()->sole();
        $this->assertSame('Fixed', $detail->rate_type);
        $this->assertNull($detail->rate);
    }

    public function test_store_requires_rate_for_non_tbb_bilty(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $payload = $this->validPayload($context, ['payment_type' => 'To Pay']);
        unset($payload['details'][0]['rate'], $payload['details'][0]['rate_type']);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $payload)
            ->assertSessionHasErrors(['details.0.rate', 'details.0.rate_type']);
    }

    public function test_store_rejects_detail_row_without_goods(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $payload = $this->validPayload($context);
        $payload['details'][] = ['good_id' => ''];

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $payload)
            ->assertSessionHasErrors('details.1.good_id');

        $this->assertDatabaseCount('bilties', 0);
    }

    public function test_store_validates_required_fields(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $this->actingAs($context['user'])
            ->from(route('bilties.create'))
            ->post(route('bilties.store'), [])
            ->assertRedirect(route('bilties.create'))
            ->assertSessionHasErrors([
                'consignor_id',
                'consignee_id',
                'from_station_id',
                'to_station_id',
                'date',
                'details',
            ]);

        $this->assertDatabaseCount('bilties', 0);
    }

    public function test_store_rejects_invalid_references_and_amounts(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $payload = $this->validPayload($context, [
            'consignor_id' => 999999,
            'from_station_id' => 999999,
            'freight' => -5,
            'status' => 'Bogus',
            'payment_type' => 'Barter',
            'gst_liability_by' => 'Nobody',
        ]);
        $payload['details'][0]['no_of_packages'] = 600000;
        $payload['details'][0]['gross_weight'] = 'heavy';

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $payload)
            ->assertSessionHasErrors([
                'consignor_id',
                'from_station_id',
                'freight',
                'status',
                'payment_type',
                'gst_liability_by',
                'details.0.no_of_packages',
                'details.0.gross_weight',
            ]);
    }

    public function test_store_rejects_duplicate_bilty_number_in_same_branch(): void
    {
        $context = $this->biltyContext(['create bilties']);
        $this->makeBilty($context, 'DUP-1');

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, ['bilty_number' => 'DUP-1']))
            ->assertSessionHasErrors('bilty_number');

        $this->assertDatabaseCount('bilties', 1);
    }

    public function test_store_cartage_requires_type_and_due_requires_account(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, ['cartage' => 100]))
            ->assertSessionHasErrors('cartage_type');

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, ['cartage' => 100, 'cartage_type' => 'due']))
            ->assertSessionHasErrors('cartage_account_id');

        $this->assertDatabaseCount('bilties', 0);
    }

    public function test_store_cash_cartage_clears_account_and_due_cartage_keeps_it(): void
    {
        $context = $this->biltyContext(['create bilties']);
        $account = $this->makeCartageAccount($context);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'bilty_number' => 'CASH-CART',
                'cartage' => 100,
                'cartage_type' => 'cash',
                'cartage_account_id' => $account->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'bilty_number' => 'DUE-CART',
                'cartage' => 100,
                'cartage_type' => 'due',
                'cartage_account_id' => $account->id,
            ]))
            ->assertSessionHasNoErrors();

        $cash = Bilty::query()->where('bilty_number', 'CASH-CART')->firstOrFail();
        $this->assertSame('Cash', $cash->cartage_type);
        $this->assertNull($cash->cartage_account_id);

        $due = Bilty::query()->where('bilty_number', 'DUE-CART')->firstOrFail();
        $this->assertSame('Due', $due->cartage_type);
        $this->assertSame($account->id, $due->cartage_account_id);
    }

    public function test_store_zero_cartage_clears_cartage_type(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'cartage' => 0,
                'cartage_type' => 'Cash',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNull(Bilty::query()->firstOrFail()->cartage_type);
    }

    public function test_store_advance_payment_mode_paths(): void
    {
        $context = $this->biltyContext(['create bilties']);
        $bank = $this->makeBankAccount($context);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, ['less_advance_received' => 100]))
            ->assertSessionHasErrors('advance_payment_mode');

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'less_advance_received' => 100,
                'advance_payment_mode' => 'bank',
            ]))
            ->assertSessionHasErrors('advance_bank_account_id');

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'bilty_number' => 'ADV-BANK',
                'less_advance_received' => 100,
                'advance_payment_mode' => 'bank',
                'advance_bank_account_id' => $bank->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'bilty_number' => 'ADV-CASH',
                'less_advance_received' => 100,
                'advance_payment_mode' => 'cash',
                'advance_bank_account_id' => $bank->id,
            ]))
            ->assertSessionHasNoErrors();

        $bankBilty = Bilty::query()->where('bilty_number', 'ADV-BANK')->firstOrFail();
        $this->assertSame('Bank', $bankBilty->advance_payment_mode);
        $this->assertSame($bank->id, $bankBilty->advance_bank_account_id);

        $cashBilty = Bilty::query()->where('bilty_number', 'ADV-CASH')->firstOrFail();
        $this->assertSame('Cash', $cashBilty->advance_payment_mode);
        $this->assertNull($cashBilty->advance_bank_account_id);
    }

    public function test_store_saves_addon_charges_and_syncs_eway_bills(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'freight' => 500,
                'addon_charges' => [
                    ['particulars' => 'Loading', 'amount' => 75],
                    ['particulars' => '', 'amount' => ''],
                ],
                'eway_bills' => [
                    ['eway_bill_no' => 'EW1234567890', 'eway_bill_valid_till' => now()->addDays(3)->toDateString()],
                    ['eway_bill_no' => '', 'eway_bill_valid_till' => ''],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $bilty = Bilty::query()->firstOrFail();
        $this->assertCount(1, $bilty->addon_charges);
        $this->assertSame('Loading', $bilty->addon_charges[0]['particulars']);
        $this->assertEqualsWithDelta(575.0, (float) $bilty->amount, 0.001);
        $this->assertSame(['EW1234567890'], $bilty->ewayBills()->pluck('eway_bill_no')->all());
    }

    public function test_store_rejects_eway_bill_without_validity_and_addon_without_particulars(): void
    {
        $context = $this->biltyContext(['create bilties']);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'eway_bills' => [['eway_bill_no' => 'EW1234567890']],
                'addon_charges' => [['amount' => 40]],
            ]))
            ->assertSessionHasErrors(['eway_bills.0.eway_bill_valid_till', 'addon_charges.0.particulars']);
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->biltyContext(['view bilties']);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context))
            ->assertForbidden();

        $this->assertDatabaseCount('bilties', 0);
    }

    public function test_show_renders_bilty_for_permitted_user(): void
    {
        $context = $this->biltyContext(['view bilties']);
        $bilty = $this->makeBilty($context, 'SHOW-1');

        $this->actingAs($context['user'])
            ->get(route('bilties.show', $bilty))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Bilties/Show')
                ->where('bilty.id', $bilty->id)
                ->has('bilty.bilty_details', 1)
                ->has('accounting')
            );
    }

    public function test_edit_page_renders_bilty_with_details_and_eway_bills(): void
    {
        $context = $this->biltyContext(['edit bilties']);
        $bilty = $this->makeBilty($context, 'EDIT-1');
        BiltyEwayBill::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $bilty->id,
            'eway_bill_no' => 'EW0000000001',
        ]);

        $this->actingAs($context['user'])
            ->get(route('bilties.edit', $bilty))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Bilties/Edit')
                ->where('bilty.id', $bilty->id)
                ->has('bilty.bilty_details', 1)
                ->has('bilty.eway_bills', 1)
                ->has('parties', 2)
                ->has('stations')
                ->has('goods')
                ->has('package_types')
                ->has('bankAccounts')
                ->has('cartageAccounts')
                ->has('agents')
                ->has('default_gst_percent')
                ->has('gst_exemption_limit')
            );
    }

    public function test_edit_page_requires_permission(): void
    {
        $context = $this->biltyContext(['view bilties']);
        $bilty = $this->makeBilty($context, 'EDIT-2');

        $this->actingAs($context['user'])->get(route('bilties.edit', $bilty))->assertForbidden();
    }

    public function test_update_replaces_details_and_resyncs_eway_bills(): void
    {
        $context = $this->biltyContext(['edit bilties']);
        $bilty = $this->makeBilty($context, 'UPD-1');
        $keep = BiltyEwayBill::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $bilty->id,
            'eway_bill_no' => 'EW-KEEP-0001',
        ]);
        BiltyEwayBill::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $bilty->id,
            'eway_bill_no' => 'EW-DROP-0002',
        ]);

        $payload = $this->validPayload($context, [
            'bilty_number' => 'UPD-1',
            'freight' => 2000,
            'private_marka' => 'Marka X',
            'eway_bills' => [
                ['id' => $keep->id, 'eway_bill_no' => 'EW-KEPT-0001', 'eway_bill_valid_till' => now()->addDay()->toDateString()],
                ['eway_bill_no' => 'EW-NEW-0003', 'eway_bill_valid_till' => now()->addDays(2)->toDateString()],
            ],
        ]);
        $payload['details'] = [
            [
                'good_id' => $context['good']->id,
                'package_type_id' => $context['packageType']->id,
                'no_of_packages' => 7,
                'gross_weight' => 70,
                'charged_weight' => 70,
                'rate_type' => 'Per Package',
                'rate' => 10,
            ],
        ];

        $this->actingAs($context['user'])
            ->put(route('bilties.update', $bilty), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('bilties.index'));

        $bilty->refresh();
        $this->assertSame('Marka X', $bilty->private_marka);
        $this->assertEqualsWithDelta(2000.0, (float) $bilty->amount, 0.001);
        $detail = $bilty->biltyDetails()->sole();
        $this->assertSame(7, (int) $detail->no_of_packages);
        $this->assertSame('Per Package', $detail->rate_type);
        $this->assertSame(
            ['EW-KEPT-0001', 'EW-NEW-0003'],
            $bilty->ewayBills()->orderBy('id')->pluck('eway_bill_no')->all()
        );
        $this->assertSame($keep->id, $bilty->ewayBills()->orderBy('id')->first()->id);
    }

    public function test_update_without_eway_bills_deletes_existing_ones(): void
    {
        $context = $this->biltyContext(['edit bilties']);
        $bilty = $this->makeBilty($context, 'UPD-2');
        BiltyEwayBill::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $bilty->id,
            'eway_bill_no' => 'EW-GONE-0001',
        ]);

        $this->actingAs($context['user'])
            ->put(route('bilties.update', $bilty), $this->validPayload($context, ['bilty_number' => 'UPD-2']))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $bilty->ewayBills()->count());
    }

    public function test_update_with_print_after_save_flashes_print_url(): void
    {
        $context = $this->biltyContext(['edit bilties']);
        $bilty = $this->makeBilty($context, 'UPD-3');

        $this->actingAs($context['user'])
            ->put(route('bilties.update', $bilty), $this->validPayload($context, [
                'bilty_number' => 'UPD-3',
                'print_after_save' => '1',
            ]))
            ->assertRedirect(route('bilties.index'))
            ->assertSessionHas('print_url', route('bilties.print', $bilty));
    }

    public function test_update_allows_keeping_own_bilty_number_but_rejects_other_numbers(): void
    {
        $context = $this->biltyContext(['edit bilties']);
        $bilty = $this->makeBilty($context, 'UPD-4');
        $this->makeBilty($context, 'UPD-5');

        $this->actingAs($context['user'])
            ->put(route('bilties.update', $bilty), $this->validPayload($context, ['bilty_number' => 'UPD-4']))
            ->assertSessionHasNoErrors();

        $this->actingAs($context['user'])
            ->put(route('bilties.update', $bilty), $this->validPayload($context, ['bilty_number' => 'UPD-5']))
            ->assertSessionHasErrors('bilty_number');
    }

    public function test_update_rejects_eway_bill_id_from_another_bilty(): void
    {
        $context = $this->biltyContext(['edit bilties']);
        $bilty = $this->makeBilty($context, 'UPD-6');
        $other = $this->makeBilty($context, 'UPD-7');
        $foreign = BiltyEwayBill::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $other->id,
            'eway_bill_no' => 'EW-OTHER-001',
        ]);

        $this->actingAs($context['user'])
            ->put(route('bilties.update', $bilty), $this->validPayload($context, [
                'bilty_number' => 'UPD-6',
                'eway_bills' => [[
                    'id' => $foreign->id,
                    'eway_bill_no' => 'EW-OTHER-001',
                    'eway_bill_valid_till' => now()->addDay()->toDateString(),
                ]],
            ]))
            ->assertSessionHasErrors('eway_bills.0.id');
    }

    public function test_update_validates_input_and_requires_permission(): void
    {
        $context = $this->biltyContext(['edit bilties']);
        $bilty = $this->makeBilty($context, 'UPD-8');

        $this->actingAs($context['user'])
            ->put(route('bilties.update', $bilty), [])
            ->assertSessionHasErrors(['consignor_id', 'consignee_id', 'details']);

        $viewer = $this->biltyContext(['view bilties'], 'C');
        $viewerBilty = $this->makeBilty($viewer, 'UPD-9');
        $this->actingAs($viewer['user'])
            ->put(route('bilties.update', $viewerBilty), $this->validPayload($viewer))
            ->assertForbidden();
    }

    public function test_destroy_soft_deletes_with_remark_and_clears_vouchers(): void
    {
        $context = $this->biltyContext(['delete bilties', 'create bilties']);

        $this->actingAs($context['user'])
            ->post(route('bilties.store'), $this->validPayload($context, [
                'payment_type' => 'Paid',
                'payment_mode' => 'Cash',
                'freight' => 900,
            ]))
            ->assertSessionHasNoErrors();

        $bilty = Bilty::query()->firstOrFail();
        $bridge = app(OperationalAccountingBridge::class);
        $this->assertGreaterThan(0, $bridge->activeVouchers($bilty)->count());

        $this->actingAs($context['user'])
            ->delete(route('bilties.destroy', $bilty), ['delete_remark' => 'Duplicate entry'])
            ->assertRedirect(route('bilties.index'))
            ->assertSessionHas('success', 'Bilty deleted successfully.');

        $this->assertSoftDeleted('bilties', ['id' => $bilty->id]);
        $this->assertDatabaseHas('bilties', [
            'id' => $bilty->id,
            'delete_remark' => 'Duplicate entry',
            'deleted_by' => $context['user']->id,
        ]);
        $this->assertCount(0, $bridge->activeVouchers($bilty));
    }

    public function test_destroy_requires_permission(): void
    {
        $context = $this->biltyContext(['view bilties', 'edit bilties']);
        $bilty = $this->makeBilty($context, 'DEL-1');

        $this->actingAs($context['user'])
            ->delete(route('bilties.destroy', $bilty))
            ->assertForbidden();

        $this->assertNotSoftDeleted('bilties', ['id' => $bilty->id]);
    }

    public function test_print_renders_view_with_charge_breakdown(): void
    {
        $context = $this->biltyContext(['view bilties']);
        $bilty = $this->makeBilty($context, 'PRN-1', [
            'freight' => 1000,
            'bilty_charge' => 50,
            'surcharge' => 20,
            'handling_charge' => 30,
            'risk_dd_charge' => 10,
            'cartage' => 40,
            'cartage_type' => 'Due',
            'less_advance_received' => 100,
            'advance_payment_mode' => 'Bank',
            'addon_charges' => [
                ['particulars' => 'Loading', 'amount' => 60],
                ['description' => 'Legacy Addon', 'amount' => 15],
                ['particulars' => 'Zero', 'amount' => 0],
            ],
        ]);

        $this->actingAs($context['user'])
            ->get(route('bilties.print', $bilty))
            ->assertOk()
            ->assertViewIs('prints.bilty')
            ->assertViewHas('bilty', fn (Bilty $viewBilty) => $viewBilty->is($bilty))
            ->assertViewHas('printData', function (array $data) {
                $labels = collect($data['charges'])->pluck('label')->all();

                return in_array('Total Freight', $labels, true)
                    && in_array('Bilty Charge', $labels, true)
                    && in_array('Cartage (Due)', $labels, true)
                    && in_array('Loading', $labels, true)
                    && in_array('Legacy Addon', $labels, true)
                    && ! in_array('Zero', $labels, true)
                    && in_array('Less Advance Received (Bank)', $labels, true)
                    && $data['less_advance'] === 100.0;
            })
            ->assertViewHas('copyPages');
    }

    public function test_print_computes_detail_row_amounts_for_each_rate_type(): void
    {
        $context = $this->biltyContext(['view bilties']);
        $bilty = $this->makeBilty($context, 'PRN-2', ['freight' => 0, 'payment_type' => 'To Pay']);
        $bilty->biltyDetails()->delete();

        foreach ([
            ['Fixed', 100],
            ['Per Package', 10],
            ['Per Actual Weight', 2],
            ['Per KG', 3],
            ['Per Charged Weight', 4],
        ] as [$rateType, $rate]) {
            BiltyDetail::create([
                'company_id' => $context['company']->id,
                'branch_id' => $context['branch']->id,
                'bilty_id' => $bilty->id,
                'good_id' => $context['good']->id,
                'package_type_id' => $context['packageType']->id,
                'no_of_packages' => 5,
                'gross_weight' => 20,
                'charged_weight' => 30,
                'rate' => $rate,
                'rate_type' => $rateType,
            ]);
        }

        $this->actingAs($context['user'])
            ->get(route('bilties.print', $bilty))
            ->assertOk()
            ->assertViewHas('printData', function (array $data) {
                return collect($data['detail_rows'])->pluck('amount')->map(fn ($v) => (float) $v)->all() === [100.0, 50.0, 40.0, 60.0, 120.0]
                    && (float) $data['detail_totals']['amount'] === 370.0;
            });
    }

    public function test_print_splits_gst_into_cgst_and_sgst_for_intra_state_bilty(): void
    {
        $context = $this->biltyContext(['view bilties']);
        $context['branch']->update(['has_gst' => true, 'gst_slab' => 12, 'gst_exemption_limit' => 0]);
        $bilty = $this->makeBilty($context, 'PRN-3', [
            'freight' => 1000,
            'gst' => 12,
            'gst_liability_by' => 'Consignee',
            'payment_type' => 'To Pay',
        ]);

        $this->actingAs($context['user'])
            ->get(route('bilties.print', $bilty))
            ->assertOk()
            ->assertViewHas('printData', function (array $data) {
                return $data['gst_amount'] != 0
                    && $data['cgst_rate'] > 0
                    && $data['sgst_rate'] > 0
                    && $data['igst_rate'] == 0;
            });
    }

    public function test_print_is_not_available_to_other_tenants(): void
    {
        $context = $this->biltyContext(['view bilties']);
        $bilty = $this->makeBilty($context, 'PRN-4');
        $other = $this->biltyContext(['view bilties'], 'B');

        $this->actingAs($other['user'])
            ->get(route('bilties.print', $bilty))
            ->assertNotFound();
    }

    public function test_bilties_are_isolated_between_tenants(): void
    {
        $context = $this->biltyContext(self::ALL_PERMISSIONS);
        $other = $this->biltyContext(self::ALL_PERMISSIONS, 'B');
        $foreign = $this->makeBilty($other, 'FOREIGN-1');

        $this->actingAs($context['user'])->get(route('bilties.show', $foreign))->assertNotFound();
        $this->actingAs($context['user'])->get(route('bilties.edit', $foreign))->assertNotFound();
        $this->actingAs($context['user'])->delete(route('bilties.destroy', $foreign))->assertNotFound();
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    private function biltyContext(array $permissions, string $suffix = 'A'): array
    {
        $context = $this->accountingContext($permissions, $suffix);

        $context['consignor'] = $this->makeParty($context, "Consignor {$suffix}");
        $context['consignee'] = $this->makeParty($context, "Consignee {$suffix}");
        $context['good'] = Good::create(['owner_id' => $context['owner']->id, 'name' => "Goods {$suffix}"]);
        $context['packageType'] = PackageType::create([
            'owner_id' => $context['owner']->id,
            'name' => "Box {$suffix}",
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
     */
    private function makeCartageAccount(array $context): Account
    {
        $group = AccountGroup::query()->where('company_id', $context['company']->id)->firstOrFail();

        return Account::create([
            'company_id' => $context['company']->id,
            'account_group_id' => $group->id,
            'name' => 'Cartage Payable',
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $attributes
     */
    private function makeBilty(array $context, string $number, array $attributes = []): Bilty
    {
        $bilty = Bilty::create(array_merge([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_number' => $number,
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'consignor_id' => $context['consignor']->id,
            'consignee_id' => $context['consignee']->id,
            'payment_type' => 'To Pay',
            'freight' => 500,
            'amount' => 500,
            'status' => 'Pending',
            'created_by' => $context['user']->id,
        ], $attributes));

        BiltyDetail::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $bilty->id,
            'good_id' => $context['good']->id,
            'package_type_id' => $context['packageType']->id,
            'no_of_packages' => 2,
            'gross_weight' => 10,
            'charged_weight' => 10,
            'rate' => 250,
            'rate_type' => 'Fixed',
        ]);

        return $bilty;
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $context, array $overrides = []): array
    {
        return array_merge([
            'consignor_id' => $context['consignor']->id,
            'consignee_id' => $context['consignee']->id,
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'date' => now()->toDateString(),
            'payment_type' => 'To Pay',
            'details' => [[
                'good_id' => $context['good']->id,
                'package_type_id' => $context['packageType']->id,
                'no_of_packages' => 5,
                'gross_weight' => 100,
                'charged_weight' => 100,
                'rate_type' => 'Fixed',
                'rate' => 250,
            ]],
        ], $overrides);
    }
}
