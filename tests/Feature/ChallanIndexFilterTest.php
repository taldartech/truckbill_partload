<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Challan;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\Station;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ChallanIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_challans_index_filters_by_search_truck_and_status(): void
    {
        $context = $this->createChallanListContext();
        $user = $context['user'];
        $match = $context['match'];

        $response = $this->actingAs($user)->get(route('challans.index', [
            'search' => 'CH-100',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
            'truck_id' => $match->truck_id,
            'status' => 'Created',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Challans/Index')
            ->where('filters.search', 'CH-100')
            ->where('filters.truck_id', (string) $match->truck_id)
            ->where('filters.status', 'Created')
            ->has('challans.data', 1)
            ->where('challans.data.0.id', $match->id)
            ->where('challans.data.0.challan_number', $match->challan_number)
            ->etc()
        );
    }

    public function test_challans_index_defaults_to_current_month_range(): void
    {
        $context = $this->createChallanListContext();

        $response = $this->actingAs($context['user'])->get(route('challans.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Challans/Index')
            ->where('filters.from_date', now()->startOfMonth()->toDateString())
            ->where('filters.to_date', now()->endOfMonth()->toDateString())
            ->has('challans.data', 1)
            ->where('challans.data.0.id', $context['match']->id)
            ->etc()
        );
    }

    /**
     * @return array{user: User, match: Challan, other: Challan}
     */
    private function createChallanListContext(): array
    {
        Permission::findOrCreate('view challans');

        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Challan Filter Co', 'user_id' => $owner->id]);

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

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Challan Branch',
            'address' => '1 Challan Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '313001',
            'station_id' => $fromStation->id,
            'contact_person' => 'Challan',
            'contact_number' => '9777777777',
            'email' => 'challan@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo('view challans');

        $truckA = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'RJ14AB1001',
        ]);
        $truckB = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'RJ14AB2002',
        ]);

        $match = Challan::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'challan_number' => 'CH-100',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'truck_id' => $truckA->id,
            'driver_name' => 'Ramesh',
            'total_amount' => 5000,
            'status' => 'Created',
        ]);

        $other = Challan::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'challan_number' => 'CH-200',
            'date' => now()->subMonths(2)->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'truck_id' => $truckB->id,
            'driver_name' => 'Suresh',
            'total_amount' => 7000,
            'status' => 'Completed',
        ]);

        return [
            'user' => $user,
            'match' => $match,
            'other' => $other,
        ];
    }
}
