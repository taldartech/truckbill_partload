<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyStationAgent;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StationShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_station_show(): void
    {
        $context = $this->createShowContext(['view stations']);

        $response = $this->actingAs($context['user'])
            ->get(route('stations.show', $context['station']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Stations/Show')
            ->where('station.id', $context['station']->id)
            ->where('station.name', 'Show Station')
            ->where('station.state.name', 'MH')
            ->where('station.city.name', 'Mumbai')
            ->where('station.country.name', 'India')
            ->where('default_agent.name', 'Show Agent')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_station_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('stations.show', $context['station']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, station: Station}
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

        $station = Station::create([
            'name' => 'Show Station',
            'owner_id' => $owner->id,
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);

        $agent = Agent::create([
            'company_id' => $company->id,
            'name' => 'Show Agent',
            'address' => '1 Show Street',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile' => '9876543210',
        ]);

        CompanyStationAgent::create([
            'company_id' => $company->id,
            'station_id' => $station->id,
            'agent_id' => $agent->id,
        ]);

        return [
            'user' => $user,
            'station' => $station,
        ];
    }
}
