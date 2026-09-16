<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use App\Support\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_select_branch_route_redirects_super_admin_to_branches_index(): void
    {
        $context = $this->createSuperAdminContext();

        $response = $this->actingAs($context['user'])
            ->get(route('branch-context.select'));

        $response->assertRedirect(route('branches.index'));
    }

    public function test_branches_index_exposes_branch_selection_for_super_admin(): void
    {
        $context = $this->createSuperAdminContext();

        $response = $this->actingAs($context['user'])
            ->get(route('branches.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Branches/Index')
            ->where('can_select_branch', true)
            ->where('active_branch_id', null)
            ->has('branches.data', 1)
            ->etc()
        );
    }

    public function test_setup_dashboard_lists_branches_for_one_click_selection(): void
    {
        $context = $this->createSuperAdminContext();

        $response = $this->actingAs($context['user'])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('dashboard.mode', 'super_admin_setup')
            ->has('dashboard.selectable_branches', 1)
            ->where('dashboard.selectable_branches.0.id', $context['branch']->id)
            ->where('dashboard.selectable_branches.0.name', $context['branch']->name)
            ->etc()
        );
    }

    public function test_super_admin_can_select_branch_from_session_endpoint(): void
    {
        $context = $this->createSuperAdminContext();

        $response = $this->actingAs($context['user'])
            ->post(route('branch-context.store'), [
                'branch_id' => $context['branch']->id,
            ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('active_branch_id', $context['branch']->id);
        $response->assertSessionHas('active_company_id', $context['company']->id);
    }

    public function test_operational_route_without_branch_redirects_to_branches_index(): void
    {
        $context = $this->createSuperAdminContext();

        $response = $this->actingAs($context['user'])
            ->get(route('bilties.index'));

        $response->assertRedirect(route('branches.index'));
        $response->assertSessionHas('error', 'Please select a branch to continue.');
    }

    /**
     * @return array{user: User, company: Company, branch: Branch}
     */
    private function createSuperAdminContext(): array
    {
        Role::findOrCreate(UserRole::SuperAdmin->value, 'web');

        foreach (RolePermissions::superAdminPermissions() as $permission) {
            Permission::findOrCreate($permission);
        }

        $user = User::factory()->create();
        $user->assignRole(UserRole::SuperAdmin->value);
        $user->givePermissionTo(RolePermissions::superAdminPermissions());

        $company = Company::create([
            'name' => 'Context Co',
            'user_id' => $user->id,
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'included_branches' => 5,
            'users_per_branch' => 10,
            'included_lr_credits' => 1000,
            'included_trucks' => 50,
            'lr_credits_remaining' => 1000,
        ]);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'MH', 'code' => 'MH']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Mumbai']);
        $station = Station::create(['name' => 'Mumbai', 'owner_id' => $user->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'HO',
            'address' => 'Addr',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'station_id' => $station->id,
            'contact_person' => 'Admin',
            'contact_number' => '9876543210',
            'email' => 'ho@example.com',
        ]);

        return [
            'user' => $user,
            'company' => $company,
            'branch' => $branch,
        ];
    }
}
