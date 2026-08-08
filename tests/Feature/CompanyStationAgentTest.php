<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyStationAgent;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CompanyStationAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_station_store_creates_company_default_agent_mapping(): void
    {
        $context = $this->createContext(['create stations']);

        $response = $this->actingAs($context['user'])
            ->withSession($this->tenantSession($context))
            ->from(route('stations.index'))
            ->post(route('stations.store'), [
                'name' => 'Pune Depot',
                'state_id' => $context['state']->id,
                'city_id' => $context['city']->id,
                'default_agent_id' => $context['agent']->id,
            ]);

        $response->assertRedirect();
        $station = Station::query()->where('name', 'Pune Depot')->first();
        $this->assertNotNull($station);

        $this->assertDatabaseHas('company_station_agents', [
            'company_id' => $context['company']->id,
            'station_id' => $station->id,
            'agent_id' => $context['agent']->id,
        ]);
    }

    public function test_station_update_can_clear_default_agent_mapping(): void
    {
        $context = $this->createContext(['edit stations']);

        CompanyStationAgent::create([
            'company_id' => $context['company']->id,
            'station_id' => $context['station']->id,
            'agent_id' => $context['agent']->id,
        ]);

        $response = $this->actingAs($context['user'])
            ->withSession($this->tenantSession($context))
            ->from(route('stations.index'))
            ->put(route('stations.update', $context['station']), [
                'name' => $context['station']->name,
                'state_id' => $context['state']->id,
                'city_id' => $context['city']->id,
                'default_agent_id' => '',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('company_station_agents', [
            'company_id' => $context['company']->id,
            'station_id' => $context['station']->id,
        ]);
    }

    public function test_default_agent_mapping_is_company_scoped(): void
    {
        $context = $this->createContext(['view stations', 'create bilties']);
        $otherCompany = Company::create([
            'name' => 'Other Co',
            'user_id' => $context['owner']->id,
        ]);
        CompanySubscription::create([
            'company_id' => $otherCompany->id,
            'status' => 'active',
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'included_branches' => 5,
            'users_per_branch' => 10,
            'included_lr_credits' => 1000,
            'included_trucks' => 50,
            'lr_credits_remaining' => 1000,
        ]);
        $otherAgent = Agent::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Agent',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
        ]);

        CompanyStationAgent::create([
            'company_id' => $context['company']->id,
            'station_id' => $context['station']->id,
            'agent_id' => $context['agent']->id,
        ]);
        CompanyStationAgent::create([
            'company_id' => $otherCompany->id,
            'station_id' => $context['station']->id,
            'agent_id' => $otherAgent->id,
        ]);

        $this->actingAs($context['user'])->withSession($this->tenantSession($context));

        $controller = app(\App\Http\Controllers\BiltyController::class);
        $method = new \ReflectionMethod($controller, 'stationOptions');
        $stations = collect($method->invoke($controller));
        $match = $stations->firstWhere('id', $context['station']->id);

        $this->assertNotNull($match);
        $this->assertSame($context['agent']->name, $match['default_agent_name']);
        $this->assertNotSame($otherAgent->name, $match['default_agent_name']);
    }

    public function test_cannot_map_agent_from_another_company(): void
    {
        $context = $this->createContext(['edit stations']);
        $otherCompany = Company::create([
            'name' => 'Foreign Co',
            'user_id' => $context['owner']->id,
        ]);
        $foreignAgent = Agent::create([
            'company_id' => $otherCompany->id,
            'name' => 'Foreign Agent',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
        ]);

        $response = $this->actingAs($context['user'])
            ->withSession($this->tenantSession($context))
            ->from(route('stations.index'))
            ->put(route('stations.update', $context['station']), [
                'name' => $context['station']->name,
                'state_id' => $context['state']->id,
                'city_id' => $context['city']->id,
                'default_agent_id' => $foreignAgent->id,
            ]);

        $response->assertRedirect(route('stations.index'));
        $response->assertSessionHasErrors(['default_agent_id']);
        $this->assertDatabaseMissing('company_station_agents', [
            'company_id' => $context['company']->id,
            'station_id' => $context['station']->id,
            'agent_id' => $foreignAgent->id,
        ]);
    }

    public function test_stations_index_includes_default_agent_id_for_active_company(): void
    {
        $context = $this->createContext(['view stations']);

        CompanyStationAgent::create([
            'company_id' => $context['company']->id,
            'station_id' => $context['station']->id,
            'agent_id' => $context['agent']->id,
        ]);

        $response = $this->actingAs($context['user'])
            ->withSession($this->tenantSession($context))
            ->get(route('stations.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Stations/Index')
            ->where('can_set_default_agent', true)
            ->has('agents')
            ->where('stations.data.0.default_agent_id', $context['agent']->id)
        );
    }

    public function test_station_quick_store_creates_company_default_agent_mapping(): void
    {
        $context = $this->createContext(['create stations']);

        $response = $this->actingAs($context['user'])
            ->withSession($this->tenantSession($context))
            ->postJson(route('stations.quick-store'), [
                'name' => 'Quick Station',
                'state_id' => $context['state']->id,
                'city_id' => $context['city']->id,
                'default_agent_id' => $context['agent']->id,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('station.default_agent_name', $context['agent']->name);

        $stationId = $response->json('station.id');
        $this->assertDatabaseHas('company_station_agents', [
            'company_id' => $context['company']->id,
            'station_id' => $stationId,
            'agent_id' => $context['agent']->id,
        ]);
    }

    public function test_station_quick_form_data_includes_agents_when_company_context_exists(): void
    {
        $context = $this->createContext(['create stations']);

        $response = $this->actingAs($context['user'])
            ->withSession($this->tenantSession($context))
            ->getJson(route('stations.quick-form-data'));

        $response->assertOk();
        $response->assertJsonPath('can_set_default_agent', true);
        $response->assertJsonFragment(['id' => $context['agent']->id, 'name' => $context['agent']->name]);
    }

    /**
     * @param  list<string>  $permissions
     * @return array{
     *     user: User,
     *     owner: User,
     *     company: Company,
     *     branch: Branch,
     *     station: Station,
     *     agent: Agent,
     *     country: Country,
     *     state: State,
     *     city: City
     * }
     */
    private function createContext(array $permissions): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Station Agent Co',
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

        $station = Station::create([
            'owner_id' => $owner->id,
            'name' => 'Mumbai',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);

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

        $agent = Agent::create([
            'company_id' => $company->id,
            'name' => 'Default Agent',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile' => '9876543210',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo($permissions);

        return [
            'user' => $user,
            'owner' => $owner,
            'company' => $company,
            'branch' => $branch,
            'station' => $station,
            'agent' => $agent,
            'country' => $country,
            'state' => $state,
            'city' => $city,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{active_company_id: int, active_branch_id: int}
     */
    private function tenantSession(array $context): array
    {
        return [
            'active_company_id' => $context['company']->id,
            'active_branch_id' => $context['branch']->id,
        ];
    }
}
