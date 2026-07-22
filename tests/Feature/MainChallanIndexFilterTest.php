<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\MainChallan;
use App\Models\State;
use App\Models\Station;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MainChallanIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_challans_index_filters_by_search_truck_and_station(): void
    {
        $context = $this->createMainChallanListContext();
        $user = $context['user'];
        $match = $context['match'];

        $response = $this->actingAs($user)->get(route('main-challans.index', [
            'search' => 'MCH-100',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
            'truck_id' => $match->truck_id,
            'from_station_id' => $match->from_station_id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('MainChallans/Index')
            ->where('filters.search', 'MCH-100')
            ->where('filters.truck_id', (string) $match->truck_id)
            ->where('filters.from_station_id', (string) $match->from_station_id)
            ->has('main_challans.data', 1)
            ->where('main_challans.data.0.id', $match->id)
            ->where('main_challans.data.0.main_challan_number', $match->main_challan_number)
            ->etc()
        );
    }

    public function test_main_challans_index_defaults_to_current_month_range(): void
    {
        $context = $this->createMainChallanListContext();

        $response = $this->actingAs($context['user'])->get(route('main-challans.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('MainChallans/Index')
            ->where('filters.from_date', now()->startOfMonth()->toDateString())
            ->where('filters.to_date', now()->endOfMonth()->toDateString())
            ->has('main_challans.data', 1)
            ->where('main_challans.data.0.id', $context['match']->id)
            ->etc()
        );
    }

    /**
     * @return array{user: User, match: MainChallan, other: MainChallan}
     */
    private function createMainChallanListContext(): array
    {
        Permission::findOrCreate('view challans');

        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Main Challan Filter Co', 'user_id' => $owner->id]);

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
        $state = State::create(['country_id' => $country->id, 'name' => 'RJ', 'code' => 'RJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Udaipur']);
        $fromStation = Station::create(['name' => 'UDR', 'owner_id' => $owner->id]);
        $toStation = Station::create(['name' => 'JPR', 'owner_id' => $owner->id]);
        $otherFromStation = Station::create(['name' => 'AMD', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main Challan Branch',
            'address' => '1 Main Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '313001',
            'station_id' => $fromStation->id,
            'contact_person' => 'Main',
            'contact_number' => '9666666666',
            'email' => 'main@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo('view challans');

        $truckA = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'RJ14MC1001',
        ]);
        $truckB = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'RJ14MC2002',
        ]);

        $match = MainChallan::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'main_challan_number' => 'MCH-100',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'truck_id' => $truckA->id,
            'driver_name' => 'Ramesh',
            'total_amount' => 8000,
        ]);

        $other = MainChallan::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'main_challan_number' => 'MCH-200',
            'date' => now()->subMonths(2)->toDateString(),
            'from_station_id' => $otherFromStation->id,
            'to_station_id' => $toStation->id,
            'truck_id' => $truckB->id,
            'driver_name' => 'Suresh',
            'total_amount' => 9000,
        ]);

        return [
            'user' => $user,
            'match' => $match,
            'other' => $other,
        ];
    }
}
