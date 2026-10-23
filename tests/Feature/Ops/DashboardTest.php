<?php

namespace Tests\Feature\Ops;

use App\Enums\AccountingVoucherType;
use App\Models\Bilty;
use App\Models\Challan;
use App\Models\Party;
use App\Models\Station;
use App\Models\Truck;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\Accounting\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_operational_dashboard_without_permissions_has_no_stats(): void
    {
        $context = $this->createTenantContext([]);
        $this->makeBilty($context, 'BLT-HIDDEN');

        $this->actingAs($context['user'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('dashboard.mode', 'operational')
                ->where('dashboard.stats', [])
                ->where('dashboard.recent_bilties', [])
                ->where('dashboard.recent_challans', [])
                ->where('dashboard.welcome.role_key', 'user')
                ->where('dashboard.welcome.role_label', 'User')
                ->where('dashboard.welcome.company', $context['company']->name)
                ->where('dashboard.welcome.branch', $context['branch']->name)
                ->where('dashboard.welcome.subtitle', 'Overview of transport operations for your branch.'));
    }

    public function test_operational_dashboard_shows_stats_and_recent_records_per_permission(): void
    {
        $context = $this->createTenantContext([
            'view bilties',
            'view challans',
            'view inward_registers',
            'view accounting_vouchers',
        ]);
        $today = $this->makeBilty($context, 'BLT-TODAY');
        $old = $this->makeBilty($context, 'BLT-OLD', now()->subMonths(2)->toDateString(), 'Delivered');
        $truck = Truck::create(['company_id' => $context['company']->id, 'truck_number' => 'RJ27AB1234']);
        Challan::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'challan_number' => 'CH-D1',
            'date' => now()->toDateString(),
            'from_station_id' => $context['station']->id,
            'to_station_id' => $context['station']->id,
            'truck_id' => $truck->id,
            'status' => 'Created',
        ]);
        $this->postVoucher($context);

        $this->actingAs($context['user'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.mode', 'operational')
                ->where('dashboard.stats.bilties', ['total' => 2, 'today' => 1, 'pending' => 1])
                ->where('dashboard.stats.challans', ['total' => 1, 'today' => 1])
                ->where('dashboard.stats.main_challans', ['total' => 0, 'today' => 0])
                ->where('dashboard.stats.inward_registers', ['total' => 0, 'today' => 0])
                ->where('dashboard.stats.vouchers', ['total' => 1, 'today' => 1])
                ->has('dashboard.recent_bilties', 2)
                ->where('dashboard.recent_bilties.0.id', $old->id)
                ->where('dashboard.recent_bilties.1.id', $today->id)
                ->has('dashboard.recent_challans', 1)
                ->where('dashboard.recent_challans.0.challan_number', 'CH-D1'));
    }

    public function test_recent_bilties_are_limited_to_five(): void
    {
        $context = $this->createTenantContext(['view bilties']);

        foreach (range(1, 7) as $i) {
            $this->makeBilty($context, "BLT-{$i}");
        }

        $this->actingAs($context['user'])
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.stats.bilties.total', 7)
                ->has('dashboard.recent_bilties', 5)
                ->missing('dashboard.stats.challans')
                ->where('dashboard.recent_challans', []));
    }

    public function test_dashboard_is_scoped_to_the_users_company(): void
    {
        $context = $this->createTenantContext(['view bilties']);
        $other = $this->createTenantContext([], 'B');
        $this->makeBilty($context, 'BLT-MINE');
        $this->makeBilty($other, 'BLT-THEIRS');

        $this->actingAs($context['user'])
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.stats.bilties.total', 1)
                ->where('dashboard.recent_bilties.0.bilty_number', 'BLT-MINE'));
    }

    public function test_moderator_and_accountant_subtitles(): void
    {
        $moderator = $this->createTenantContext([]);
        $this->giveRole($moderator['user'], 'moderator');

        $this->actingAs($moderator['user'])
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.welcome.role_key', 'moderator')
                ->where('dashboard.welcome.role_label', 'Moderator')
                ->where('dashboard.welcome.subtitle', 'Create and manage daily transport transactions for your branch.'));

        $accountant = $this->createTenantContext([], 'B');
        $this->giveRole($accountant['user'], 'accountant');

        $this->actingAs($accountant['user'])
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.welcome.role_label', 'Accountant')
                ->where('dashboard.welcome.subtitle', 'Review records, vouchers, and reports for your branch.'));
    }

    public function test_master_admin_sees_platform_stats(): void
    {
        $context = $this->createTenantContext([]);
        $this->giveRole($context['user'], 'master_admin');
        $this->createTenantContext([], 'B', 'active');
        $this->createTenantContext([], 'C', 'expired');

        $this->actingAs($context['user'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.mode', 'master_admin')
                ->where('dashboard.welcome.role_key', 'master_admin')
                ->where('dashboard.welcome.subtitle', 'Manage platform users, roles, and subscriptions.')
                ->where('dashboard.stats.super_admins', 0)
                ->where('dashboard.stats.companies', 3)
                ->where('dashboard.stats.active_subscriptions', 2));
    }

    public function test_super_admin_without_branch_sees_setup_mode(): void
    {
        $context = $this->createTenantContext([]);
        $owner = $context['owner'];
        $this->giveRole($owner, 'super_admin');

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.mode', 'super_admin_setup')
                ->where('dashboard.stats.companies', 1)
                ->where('dashboard.stats.branches', 1)
                ->where('dashboard.stats.users', 1)
                ->has('dashboard.selectable_branches', 1)
                ->where('dashboard.selectable_branches.0.id', $context['branch']->id)
                ->where('dashboard.selectable_branches.0.company.name', $context['company']->name)
                ->where('dashboard.welcome.role_label', 'Super Admin'));
    }

    public function test_super_admin_acting_in_branch_sees_operational_dashboard_as_admin(): void
    {
        $context = $this->createTenantContext([]);
        $owner = $context['owner'];
        $this->giveRole($owner, 'super_admin');
        $this->makeBilty($context, 'BLT-ADMIN');

        $this->actingAs($owner)
            ->withSession([
                'active_branch_id' => $context['branch']->id,
                'active_company_id' => $context['company']->id,
            ])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.mode', 'operational')
                ->where('dashboard.welcome.role_key', 'admin')
                ->where('dashboard.welcome.role_label', 'Admin')
                ->where('dashboard.stats.bilties.total', 1)
                ->where('dashboard.recent_bilties.0.bilty_number', 'BLT-ADMIN'));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeBilty(array $context, string $number, ?string $date = null, string $status = 'Pending'): Bilty
    {
        $consignor = Party::query()->firstOrCreate(
            ['company_id' => $context['company']->id, 'name' => 'Consignor'],
            ['state_id' => $context['state']->id, 'city_id' => $context['city']->id]
        );
        $consignee = Party::query()->firstOrCreate(
            ['company_id' => $context['company']->id, 'name' => 'Consignee'],
            ['state_id' => $context['state']->id, 'city_id' => $context['city']->id]
        );
        $toStation = Station::query()->withoutGlobalScopes()->firstOrCreate(
            ['owner_id' => $context['owner']->id, 'name' => 'Destination'],
            ['state_id' => $context['state']->id, 'city_id' => $context['city']->id, 'country_id' => $context['country']->id]
        );

        return Bilty::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_number' => $number,
            'date' => $date ?? now()->toDateString(),
            'from_station_id' => $context['station']->id,
            'to_station_id' => $toStation->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'freight' => 100,
            'status' => $status,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function postVoucher(array $context): void
    {
        app(EnsureCompanyAccounting::class)->handle($context['company']);
        $chart = app(ChartOfAccountsService::class);

        app(VoucherService::class)->post([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'voucher_type' => AccountingVoucherType::Journal,
            'date' => now()->toDateString(),
        ], [
            ['account_id' => $chart->systemAccount($context['company'], 'cash')->id, 'debit' => 10, 'credit' => 0],
            ['account_id' => $chart->systemAccount($context['company'], 'owners_capital')->id, 'debit' => 0, 'credit' => 10],
        ]);
    }
}
