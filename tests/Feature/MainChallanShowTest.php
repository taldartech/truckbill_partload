<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Challan;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\MainChallan;
use App\Models\MainChallanDetail;
use App\Models\State;
use App\Models\Station;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MainChallanShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_main_challan_show(): void
    {
        $context = $this->createShowContext(['view challans']);

        $response = $this->actingAs($context['user'])
            ->get(route('main-challans.show', $context['main_challan']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('MainChallans/Show')
            ->where('main_challan.id', $context['main_challan']->id)
            ->where('main_challan.main_challan_number', 'SHOW-MCH-100')
            ->where('main_challan.from_station.name', 'From Show')
            ->where('main_challan.to_station.name', 'To Show')
            ->where('main_challan.truck.truck_number', 'MH12AB1234')
            ->has('main_challan.details', 1)
            ->where('main_challan.details.0.challan.challan_number', 'SHOW-CH-100')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_main_challan_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('main-challans.show', $context['main_challan']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, main_challan: MainChallan}
     */
    private function createShowContext(array $permissions): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Main Challan Show Co',
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
        $fromStation = Station::create(['name' => 'From Show', 'owner_id' => $owner->id]);
        $toStation = Station::create(['name' => 'To Show', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main Challan Show Branch',
            'address' => '1 Main Challan Show Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'station_id' => $fromStation->id,
            'contact_person' => 'Show Admin',
            'contact_number' => '9876543210',
            'email' => 'main-challan-show@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'MH12AB1234',
        ]);

        $challan = Challan::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'challan_number' => 'SHOW-CH-100',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'truck_id' => $truck->id,
            'driver_name' => 'Show Driver',
            'total_amount' => 500,
            'total_packages' => 2,
            'total_weight' => 10,
            'status' => 'In Progress',
        ]);

        $mainChallan = MainChallan::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'main_challan_number' => 'SHOW-MCH-100',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'truck_id' => $truck->id,
            'driver_name' => 'Show Driver',
            'truck_freight' => 5000,
            'total_amount' => 5000,
            'pay_by' => 'Driver',
            'created_by' => $user->id,
        ]);

        MainChallanDetail::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'main_challan_id' => $mainChallan->id,
            'challan_id' => $challan->id,
        ]);

        return [
            'user' => $user,
            'main_challan' => $mainChallan,
        ];
    }
}
