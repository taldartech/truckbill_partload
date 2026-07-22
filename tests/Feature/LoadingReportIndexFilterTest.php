<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\LoadingReport;
use App\Models\State;
use App\Models\Station;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LoadingReportIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_loading_reports_index_filters_by_search_truck_and_station(): void
    {
        $context = $this->createLoadingReportListContext();
        $user = $context['user'];
        $match = $context['match'];

        $response = $this->actingAs($user)->get(route('loading-reports.index', [
            'search' => 'Ramesh',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
            'truck_id' => $match->truck_id,
            'from_station_id' => $match->from_station_id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('LoadingReports/Index')
            ->where('filters.search', 'Ramesh')
            ->where('filters.truck_id', (string) $match->truck_id)
            ->where('filters.from_station_id', (string) $match->from_station_id)
            ->has('reports.data', 1)
            ->where('reports.data.0.id', $match->id)
            ->etc()
        );
    }

    public function test_loading_reports_index_defaults_to_current_month_range(): void
    {
        $context = $this->createLoadingReportListContext();

        $response = $this->actingAs($context['user'])->get(route('loading-reports.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('LoadingReports/Index')
            ->where('filters.from_date', now()->startOfMonth()->toDateString())
            ->where('filters.to_date', now()->endOfMonth()->toDateString())
            ->has('reports.data', 1)
            ->where('reports.data.0.id', $context['match']->id)
            ->etc()
        );
    }

    /**
     * @return array{user: User, match: LoadingReport, other: LoadingReport}
     */
    private function createLoadingReportListContext(): array
    {
        Permission::findOrCreate('view loading_reports');

        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Loading Filter Co', 'user_id' => $owner->id]);

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
            'name' => 'Loading Branch',
            'address' => '1 Loading Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '313001',
            'station_id' => $fromStation->id,
            'contact_person' => 'Loader',
            'contact_number' => '9555555555',
            'email' => 'loading@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo('view loading_reports');

        $truckA = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'RJ14LR1001',
        ]);
        $truckB = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'RJ14LR2002',
        ]);

        $match = LoadingReport::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'truck_id' => $truckA->id,
            'driver_name' => 'Ramesh',
            'gross_weight' => 100,
            'no_of_packages' => 10,
        ]);

        $other = LoadingReport::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'date' => now()->subMonths(2)->toDateString(),
            'from_station_id' => $otherFromStation->id,
            'to_station_id' => $toStation->id,
            'truck_id' => $truckB->id,
            'driver_name' => 'Suresh',
            'gross_weight' => 200,
            'no_of_packages' => 20,
        ]);

        return [
            'user' => $user,
            'match' => $match,
            'other' => $other,
        ];
    }
}
