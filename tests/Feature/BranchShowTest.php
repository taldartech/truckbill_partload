<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BranchShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_branch_show(): void
    {
        $context = $this->createShowContext(['view branches']);

        $response = $this->actingAs($context['user'])
            ->get(route('branches.show', $context['branch']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Branches/Show')
            ->where('branch.id', $context['branch']->id)
            ->where('branch.name', 'Show Branch')
            ->where('branch.station.name', 'From Show')
            ->where('branch.city.name', 'Mumbai')
            ->where('branch.state.name', 'MH')
            ->where('branch.company.name', 'Show Co')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_branch_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('branches.show', $context['branch']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, branch: Branch}
     */
    private function createShowContext(array $permissions): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Show Co',
            'user_id' => $owner->id,
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
        $station = Station::create(['name' => 'From Show', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Show Branch',
            'address' => '1 Show Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'station_id' => $station->id,
            'contact_person' => 'Show Admin',
            'contact_number' => '9876543210',
            'email' => 'show@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return [
            'user' => $user,
            'branch' => $branch,
        ];
    }
}
