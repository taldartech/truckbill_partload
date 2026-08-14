<?php

namespace Tests\Feature;

use App\Models\Bilty;
use App\Models\BiltyDetail;
use App\Models\Branch;
use App\Models\Challan;
use App\Models\ChallanDetail;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\Good;
use App\Models\PackageType;
use App\Models\Party;
use App\Models\State;
use App\Models\Station;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ChallanShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_challan_show(): void
    {
        $context = $this->createShowContext(['view challans']);

        $response = $this->actingAs($context['user'])
            ->get(route('challans.show', $context['challan']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Challans/Show')
            ->where('challan.id', $context['challan']->id)
            ->where('challan.challan_number', 'SHOW-CH-100')
            ->where('challan.from_station.name', 'From Show')
            ->where('challan.to_station.name', 'To Show')
            ->where('challan.truck.truck_number', 'MH12AB1234')
            ->has('challan.details', 1)
            ->where('challan.details.0.bilty.bilty_number', 'SHOW-BLT-100')
            ->where('challan.details.0.bilty.consignee.name', 'Show Consignee')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_challan_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('challans.show', $context['challan']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, challan: Challan}
     */
    private function createShowContext(array $permissions): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Challan Show Co',
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
            'name' => 'Challan Show Branch',
            'address' => '1 Challan Show Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'station_id' => $fromStation->id,
            'contact_person' => 'Show Admin',
            'contact_number' => '9876543210',
            'email' => 'challan-show@example.com',
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

        $consignor = Party::create([
            'company_id' => $company->id,
            'name' => 'Show Consignor',
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);
        $consignee = Party::create([
            'company_id' => $company->id,
            'name' => 'Show Consignee',
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);

        $good = Good::create(['owner_id' => $owner->id, 'name' => 'Show Goods']);
        $packageType = PackageType::create(['owner_id' => $owner->id, 'name' => 'Box']);

        $bilty = Bilty::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'bilty_number' => 'SHOW-BLT-100',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'freight' => 500,
            'status' => 'In Transit',
        ]);

        BiltyDetail::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'bilty_id' => $bilty->id,
            'good_id' => $good->id,
            'package_type_id' => $packageType->id,
            'no_of_packages' => 2,
            'gross_weight' => 10,
            'charged_weight' => 10,
            'rate' => 250,
            'rate_type' => 'Fixed',
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
            'booking_freight' => 500,
            'total_amount' => 500,
            'status' => 'Created',
            'created_by' => $user->id,
        ]);

        ChallanDetail::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'challan_id' => $challan->id,
            'bilty_id' => $bilty->id,
            'to_station_id' => $toStation->id,
        ]);

        return [
            'user' => $user,
            'challan' => $challan,
        ];
    }
}
