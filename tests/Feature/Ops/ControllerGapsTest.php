<?php

namespace Tests\Feature\Ops;

use App\Enums\AccountingVoucherType;
use App\Enums\UserRole;
use App\Models\AccountingVoucher;
use App\Models\Challan;
use App\Models\MainChallan;
use App\Models\MainChallanDetail;
use App\Models\Truck;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\VoucherService;
use App\Services\OnboardingProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class ControllerGapsTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    private function postManualVoucher(array $context, string $narration = 'Manual'): AccountingVoucher
    {
        $chart = app(ChartOfAccountsService::class);
        $cash = $chart->systemAccount($context['company'], 'cash');
        $capital = $chart->systemAccount($context['company'], 'owners_capital');

        return app(VoucherService::class)->post([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'voucher_type' => AccountingVoucherType::Journal,
            'date' => now()->toDateString(),
            'narration' => $narration,
        ], [
            ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 100],
        ]);
    }

    public function test_accounting_voucher_index_applies_all_filters(): void
    {
        $context = $this->createTenantContext(['view accounting_vouchers']);
        $voucher = $this->postManualVoucher($context, 'Searchable narration');

        $this->actingAs($context['user'])
            ->get(route('accounting.vouchers.index', [
                'search' => 'Searchable',
                'voucher_type' => AccountingVoucherType::Journal->value,
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Accounting/Vouchers/Index')
                ->has('vouchers.data', 1)
                ->where('vouchers.data.0.id', $voucher->id)
                ->has('voucherTypes')
            );

        $this->actingAs($context['user'])
            ->get(route('accounting.vouchers.index', ['search' => 'no-such-text']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('vouchers.data', 0));

        $this->actingAs($context['user'])
            ->from(route('accounting.vouchers.index'))
            ->get(route('accounting.vouchers.index', ['start_date' => '2026-02-02', 'end_date' => '2026-01-01']))
            ->assertSessionHasErrors('end_date');
    }

    public function test_manual_accounting_voucher_can_be_cancelled_and_reversed(): void
    {
        $context = $this->createTenantContext(['view accounting_vouchers', 'edit accounting_vouchers']);
        $toCancel = $this->postManualVoucher($context, 'To cancel');
        $toReverse = $this->postManualVoucher($context, 'To reverse');

        $this->actingAs($context['user'])
            ->from(route('accounting.vouchers.index'))
            ->post(route('accounting.vouchers.cancel', $toCancel), ['reason' => 'Entered twice'])
            ->assertRedirect(route('accounting.vouchers.index'))
            ->assertSessionHas('success', 'Voucher cancelled.');

        $this->assertSame('Entered twice', $toCancel->fresh()->cancel_reason);

        $response = $this->actingAs($context['user'])
            ->post(route('accounting.vouchers.reverse', $toReverse));

        $reversal = AccountingVoucher::query()->where('reversed_voucher_id', $toReverse->id)->firstOrFail();
        $response->assertRedirect(route('accounting.vouchers.show', $reversal))
            ->assertSessionHas('success', 'Reversal voucher posted.');
    }

    public function test_legacy_voucher_routes_redirect_to_accounting_vouchers(): void
    {
        $context = $this->createTenantContext(['view vouchers']);
        $user = $context['user'];

        $this->actingAs($user)->get(route('vouchers.index'))->assertRedirect(route('accounting.vouchers.index'));
        $this->actingAs($user)->get(route('vouchers.create'))->assertRedirect(route('accounting.vouchers.create', ['type' => 'receipt']));
        $this->actingAs($user)->post(route('vouchers.store'))->assertRedirect(route('accounting.vouchers.create'));
        $this->actingAs($user)->get(route('vouchers.show', 1))->assertRedirect(route('accounting.vouchers.index'));
        $this->actingAs($user)->get(route('vouchers.edit', 1))->assertRedirect(route('accounting.vouchers.index'));
        $this->actingAs($user)->put(route('vouchers.update', 1))->assertRedirect(route('accounting.vouchers.index'));
        $this->actingAs($user)->delete(route('vouchers.destroy', 1))->assertRedirect(route('accounting.vouchers.index'));
        $this->actingAs($user)->get(route('vouchers.entity-balance'))->assertOk()->assertJson(['outstanding' => 0]);
    }

    public function test_branch_context_authorisation_and_clearing(): void
    {
        $context = $this->createTenantContext();
        $plain = $context['user'];

        $this->actingAs($plain)->get(route('branch-context.select'))->assertRedirect(route('dashboard'));
        $this->actingAs($plain)->post(route('branch-context.store'), ['branch_id' => $context['branch']->id])->assertForbidden();
        $this->actingAs($plain)->delete(route('branch-context.destroy'))->assertForbidden();

        Role::findOrCreate(UserRole::SuperAdmin->value, 'web');
        $stranger = User::factory()->create();
        $stranger->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($stranger)
            ->post(route('branch-context.store'), ['branch_id' => $context['branch']->id])
            ->assertNotFound();

        $owner = $context['owner'];
        $owner->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($owner)
            ->withSession(['active_branch_id' => $context['branch']->id, 'active_company_id' => $context['company']->id])
            ->delete(route('branch-context.destroy'))
            ->assertRedirect(route('branches.index'))
            ->assertSessionMissing('active_branch_id')
            ->assertSessionMissing('active_company_id');
    }

    public function test_main_challan_create_and_edit_pages_render(): void
    {
        $context = $this->createTenantContext(['create challans', 'edit challans']);
        $company = $context['company'];

        $truck = Truck::create(['company_id' => $company->id, 'truck_number' => 'MH12ZZ0001']);
        $challan = Challan::create([
            'company_id' => $company->id,
            'branch_id' => $context['branch']->id,
            'challan_number' => 'CH-GAP-1',
            'date' => now()->toDateString(),
            'from_station_id' => $context['station']->id,
            'to_station_id' => $context['station']->id,
            'truck_id' => $truck->id,
            'driver_name' => 'Driver',
            'total_amount' => 10,
            'total_packages' => 1,
            'total_weight' => 1,
            'status' => 'Created',
        ]);
        $mainChallan = MainChallan::create([
            'company_id' => $company->id,
            'branch_id' => $context['branch']->id,
            'main_challan_number' => 'MCH-GAP-1',
            'date' => now()->toDateString(),
            'from_station_id' => $context['station']->id,
            'to_station_id' => $context['station']->id,
            'truck_id' => $truck->id,
            'driver_name' => 'Driver',
            'truck_freight' => 100,
            'total_amount' => 100,
            'created_by' => $context['user']->id,
        ]);
        MainChallanDetail::create([
            'company_id' => $company->id,
            'branch_id' => $context['branch']->id,
            'main_challan_id' => $mainChallan->id,
            'challan_id' => $challan->id,
        ]);

        $this->actingAs($context['user'])
            ->get(route('main-challans.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('MainChallans/Create')
                ->has('challans', 1)
                ->has('next_main_challan_number')
                ->etc()
            );

        $this->actingAs($context['user'])
            ->get(route('main-challans.edit', $mainChallan))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('MainChallans/Edit')
                ->where('main_challan.id', $mainChallan->id)
                ->etc()
            );
    }

    public function test_onboarding_update_user_endpoint_validates_and_updates(): void
    {
        config(['onboarding.signing_secret' => 'gap-secret']);
        $service = app(OnboardingProvisioningService::class);
        $service->createUser([
            'idempotency_key' => 'gap-1',
            'name' => 'Gap Owner',
            'email' => 'gap@example.com',
            'password' => 'password123',
        ]);

        $body = json_encode(['email' => 'gap@example.com', 'name' => 'Renamed Owner'], JSON_THROW_ON_ERROR);
        $timestamp = (string) time();

        $response = $this->call(
            'POST',
            '/api/onboarding/update-user',
            [],
            [],
            [],
            [
                'HTTP_X-Timestamp' => $timestamp,
                'HTTP_X-Signature' => hash_hmac('sha256', $timestamp.'.'.$body, 'gap-secret'),
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            $body
        );

        $response->assertOk()->assertJsonPath('exists', true);
        $this->assertSame('Renamed Owner', User::query()->where('email', 'gap@example.com')->value('name'));
    }
}
