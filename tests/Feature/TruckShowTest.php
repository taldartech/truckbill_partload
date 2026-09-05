<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\Truck;
use App\Models\TruckOwner;
use App\Models\TruckType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TruckShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_truck_show(): void
    {
        $context = $this->createShowContext(['view trucks']);

        $response = $this->actingAs($context['user'])
            ->get(route('trucks.show', $context['truck']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Trucks/Show')
            ->where('truck.id', $context['truck']->id)
            ->where('truck.truck_number', 'MH-SHOW-3')
            ->where('truck.truck_type.name', 'Show Truck Type')
            ->where('truck.current_state.name', 'MH')
            ->where('truck.current_city.name', 'Mumbai')
            ->where('truck.truck_owner.name', 'Show Owner')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_truck_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('trucks.show', $context['truck']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, truck: Truck}
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

        $truckType = TruckType::create(['owner_id' => $owner->id, 'name' => 'Show Truck Type']);

        $truckOwner = TruckOwner::create([
            'company_id' => $company->id,
            'name' => 'Show Owner',
            'address' => '1 Owner Street',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile' => '9876543210',
        ]);

        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'MH-SHOW-3',
            'truck_type_id' => $truckType->id,
            'current_state_id' => $state->id,
            'current_city_id' => $city->id,
            'truck_owner_id' => $truckOwner->id,
            'rc_no' => 'RC-1',
            'rc_expiry_date' => now()->addYear()->toDateString(),
        ]);

        return [
            'user' => $user,
            'truck' => $truck,
        ];
    }
}
