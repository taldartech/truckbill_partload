<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\TruckOwner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TruckOwnerShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_truck_owner_show(): void
    {
        $context = $this->createShowContext(['view truck_owners']);

        $response = $this->actingAs($context['user'])
            ->get(route('truck-owners.show', $context['truckOwner']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('TruckOwners/Show')
            ->where('truckOwner.id', $context['truckOwner']->id)
            ->where('truckOwner.name', 'Show Truck Owner')
            ->where('truckOwner.state.name', 'MH')
            ->where('truckOwner.city.name', 'Mumbai')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_truck_owner_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('truck-owners.show', $context['truckOwner']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, truckOwner: TruckOwner}
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

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $truckOwner = TruckOwner::create([
            'company_id' => $company->id,
            'name' => 'Show Truck Owner',
            'address' => '1 Show Street',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile' => '9876543210',
            'email' => 'show-truck-owner@example.com',
            'gst_number' => '27AAAAA0000A1Z5',
        ]);

        return [
            'user' => $user,
            'truckOwner' => $truckOwner,
        ];
    }
}
