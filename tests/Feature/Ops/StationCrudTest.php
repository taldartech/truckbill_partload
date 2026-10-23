<?php

namespace Tests\Feature\Ops;

use App\Models\Agent;
use App\Models\City;
use App\Models\CompanyStationAgent;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class StationCrudTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_index_lists_stations_and_supports_search(): void
    {
        $context = $this->createTenantContext(['view stations']);
        $this->makeStation($context, 'Jaipur Hub');

        $this->actingAs($context['user'])
            ->get(route('stations.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Stations/Index')
                ->has('stations.data', 2)
                ->where('can_set_default_agent', true)
                ->has('states')
                ->has('cities')
                ->has('agents')
            );

        $this->actingAs($context['user'])
            ->get(route('stations.index', ['search' => 'Jaipur']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('stations.data', 1)
                ->where('stations.data.0.name', 'Jaipur Hub')
                ->where('filters.search', 'Jaipur')
            );
    }

    public function test_index_search_matches_city_name(): void
    {
        $context = $this->createTenantContext(['view stations']);

        $this->actingAs($context['user'])
            ->get(route('stations.index', ['search' => 'Udaipur']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('stations.data', 1));
    }

    public function test_index_rejects_overlong_search(): void
    {
        $context = $this->createTenantContext(['view stations']);

        $this->actingAs($context['user'])
            ->from(route('stations.index'))
            ->get(route('stations.index', ['search' => str_repeat('a', 101)]))
            ->assertSessionHasErrors('search');
    }

    public function test_index_requires_permission(): void
    {
        $context = $this->createTenantContext([]);

        $this->actingAs($context['user'])
            ->get(route('stations.index'))
            ->assertForbidden();
    }

    public function test_create_and_edit_redirect_to_index(): void
    {
        $context = $this->createTenantContext(['create stations', 'edit stations']);

        $this->actingAs($context['user'])
            ->get(route('stations.create'))
            ->assertRedirect(route('stations.index'));

        $this->actingAs($context['user'])
            ->get(route('stations.edit', $context['station']))
            ->assertRedirect(route('stations.index'));
    }

    public function test_show_renders_station(): void
    {
        $context = $this->createTenantContext(['view stations']);

        $this->actingAs($context['user'])
            ->get(route('stations.show', $context['station']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Stations/Show')
                ->where('station.id', $context['station']->id)
                ->where('default_agent', null)
            );
    }

    public function test_store_creates_station(): void
    {
        $context = $this->createTenantContext(['create stations']);

        $this->actingAs($context['user'])
            ->from(route('stations.index'))
            ->post(route('stations.store'), [
                'name' => 'Kota Junction',
                'city_id' => $context['city']->id,
                'state_id' => $context['state']->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('stations.index'))
            ->assertSessionHas('success', 'Station created successfully.');

        $this->assertDatabaseHas('stations', [
            'name' => 'Kota Junction',
            'owner_id' => $context['owner']->id,
        ]);
    }

    public function test_store_with_default_agent_creates_mapping(): void
    {
        $context = $this->createTenantContext(['create stations']);
        $agent = $this->makeAgent($context);

        $this->actingAs($context['user'])
            ->post(route('stations.store'), [
                'name' => 'Kota Junction',
                'city_id' => $context['city']->id,
                'state_id' => $context['state']->id,
                'default_agent_id' => $agent->id,
            ])
            ->assertSessionHasNoErrors();

        $station = Station::query()->where('name', 'Kota Junction')->firstOrFail();
        $this->assertDatabaseHas('company_station_agents', [
            'company_id' => $context['company']->id,
            'station_id' => $station->id,
            'agent_id' => $agent->id,
        ]);
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->createTenantContext(['view stations']);

        $this->actingAs($context['user'])
            ->post(route('stations.store'), [
                'name' => 'Blocked',
                'city_id' => $context['city']->id,
                'state_id' => $context['state']->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('stations', ['name' => 'Blocked']);
    }

    public function test_store_validates_required_fields_and_references(): void
    {
        $context = $this->createTenantContext(['create stations']);

        $this->actingAs($context['user'])
            ->post(route('stations.store'), [])
            ->assertSessionHasErrors(['name', 'city_id', 'state_id']);

        $this->actingAs($context['user'])
            ->post(route('stations.store'), [
                'name' => str_repeat('x', 256),
                'city_id' => 999999,
                'state_id' => 999999,
            ])
            ->assertSessionHasErrors(['name', 'city_id', 'state_id']);
    }

    public function test_store_rejects_default_agent_from_another_company(): void
    {
        $context = $this->createTenantContext(['create stations']);
        $other = $this->createTenantContext([], 'B');
        $foreignAgent = $this->makeAgent($other);

        $this->actingAs($context['user'])
            ->post(route('stations.store'), [
                'name' => 'Cross Tenant',
                'city_id' => $context['city']->id,
                'state_id' => $context['state']->id,
                'default_agent_id' => $foreignAgent->id,
            ])
            ->assertSessionHasErrors('default_agent_id');

        $this->assertDatabaseMissing('stations', ['name' => 'Cross Tenant']);
    }

    public function test_store_ignores_client_supplied_owner_id(): void
    {
        $context = $this->createTenantContext(['create stations']);
        $other = $this->createTenantContext([], 'B');

        $this->actingAs($context['user'])
            ->post(route('stations.store'), [
                'name' => 'Owner Injection',
                'city_id' => $context['city']->id,
                'state_id' => $context['state']->id,
                'owner_id' => $other['owner']->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('stations', [
            'name' => 'Owner Injection',
            'owner_id' => $context['owner']->id,
        ]);
    }

    public function test_update_changes_station_and_syncs_default_agent(): void
    {
        $context = $this->createTenantContext(['edit stations']);
        $agent = $this->makeAgent($context);

        $this->actingAs($context['user'])
            ->put(route('stations.update', $context['station']), [
                'name' => 'Renamed Station',
                'city_id' => $context['city']->id,
                'state_id' => $context['state']->id,
                'default_agent_id' => $agent->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Station updated successfully.');

        $this->assertSame('Renamed Station', $context['station']->fresh()->name);
        $this->assertDatabaseHas('company_station_agents', [
            'station_id' => $context['station']->id,
            'agent_id' => $agent->id,
        ]);

        $this->actingAs($context['user'])
            ->put(route('stations.update', $context['station']), [
                'name' => 'Renamed Station',
                'city_id' => $context['city']->id,
                'state_id' => $context['state']->id,
                'default_agent_id' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('company_station_agents', [
            'station_id' => $context['station']->id,
        ]);
    }

    public function test_update_validates_and_requires_permission(): void
    {
        $context = $this->createTenantContext(['edit stations']);

        $this->actingAs($context['user'])
            ->put(route('stations.update', $context['station']), ['name' => ''])
            ->assertSessionHasErrors(['name', 'city_id', 'state_id']);

        $viewer = $this->createTenantContext(['view stations'], 'C');
        $this->actingAs($viewer['user'])
            ->put(route('stations.update', $viewer['station']), [
                'name' => 'Nope',
                'city_id' => $viewer['city']->id,
                'state_id' => $viewer['state']->id,
            ])
            ->assertForbidden();
    }

    public function test_destroy_soft_deletes_with_remark(): void
    {
        $context = $this->createTenantContext(['delete stations']);

        $this->actingAs($context['user'])
            ->delete(route('stations.destroy', $context['station']), ['delete_remark' => 'Duplicate'])
            ->assertRedirect(route('stations.index'))
            ->assertSessionHas('success', 'Station deleted successfully.');

        $this->assertSoftDeleted('stations', ['id' => $context['station']->id]);
        $this->assertDatabaseHas('stations', [
            'id' => $context['station']->id,
            'delete_remark' => 'Duplicate',
            'deleted_by' => $context['user']->id,
        ]);
    }

    public function test_destroy_requires_permission(): void
    {
        $context = $this->createTenantContext(['view stations', 'edit stations']);

        $this->actingAs($context['user'])
            ->delete(route('stations.destroy', $context['station']))
            ->assertForbidden();

        $this->assertNotSoftDeleted('stations', ['id' => $context['station']->id]);
    }

    public function test_quick_form_data_returns_states_and_agents(): void
    {
        $context = $this->createTenantContext(['create stations']);
        $this->makeAgent($context);

        $this->actingAs($context['user'])
            ->getJson(route('stations.quick-form-data'))
            ->assertOk()
            ->assertJsonPath('can_set_default_agent', true)
            ->assertJsonCount(1, 'agents')
            ->assertJsonStructure(['states', 'agents', 'can_set_default_agent']);
    }

    public function test_quick_show_returns_rows(): void
    {
        $context = $this->createTenantContext(['view stations']);

        $this->actingAs($context['user'])
            ->getJson(route('stations.quick', $context['station']))
            ->assertOk()
            ->assertJsonPath('rows.0.label', 'Name')
            ->assertJsonPath('rows.0.value', $context['station']->name)
            ->assertJsonPath('rows.1.value', 'Rajasthan')
            ->assertJsonPath('rows.2.value', 'Udaipur');
    }

    public function test_quick_store_creates_station_and_returns_payload(): void
    {
        $context = $this->createTenantContext(['create stations']);
        $agent = $this->makeAgent($context);

        $this->actingAs($context['user'])
            ->postJson(route('stations.quick-store'), [
                'name' => 'Quick Station',
                'city_id' => $context['city']->id,
                'state_id' => $context['state']->id,
                'default_agent_id' => $agent->id,
            ])
            ->assertCreated()
            ->assertJsonPath('station.name', 'Quick Station')
            ->assertJsonPath('station.state_id', $context['state']->id)
            ->assertJsonPath('station.country_id', $context['country']->id)
            ->assertJsonPath('station.default_agent_name', 'Quick Agent');

        $this->assertSame(1, CompanyStationAgent::query()->where('agent_id', $agent->id)->count());
    }

    public function test_quick_store_validates_input_and_permission(): void
    {
        $context = $this->createTenantContext(['create stations']);

        $this->actingAs($context['user'])
            ->postJson(route('stations.quick-store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'city_id', 'state_id']);

        $viewer = $this->createTenantContext(['view stations'], 'C');
        $this->actingAs($viewer['user'])
            ->postJson(route('stations.quick-store'), [
                'name' => 'Blocked',
                'city_id' => $viewer['city']->id,
                'state_id' => $viewer['state']->id,
            ])
            ->assertForbidden();
    }

    public function test_bulk_upload_template_download(): void
    {
        $context = $this->createTenantContext(['create stations']);

        $response = $this->actingAs($context['user'])
            ->get(route('stations.bulk-upload.template'));

        $response->assertOk();
        $this->assertStringContainsString(
            'stations_bulk_upload_template.csv',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_bulk_upload_template_requires_permission(): void
    {
        $context = $this->createTenantContext(['view stations']);

        $this->actingAs($context['user'])
            ->get(route('stations.bulk-upload.template'))
            ->assertForbidden();
    }

    public function test_bulk_upload_creates_stations_with_default_agent(): void
    {
        $context = $this->createTenantContext(['create stations']);
        $agent = $this->makeAgent($context);

        $csv = "Station Name,State,City,Default Agent\n"
            ."Bhilwara Depot,Rajasthan,Udaipur,Quick Agent\n"
            ."Chittor Depot,RJ,Udaipur,\n";

        $this->actingAs($context['user'])
            ->postJson(route('stations.bulk-upload'), [
                'file' => UploadedFile::fake()->createWithContent('stations.csv', $csv),
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'summary' => ['total' => 2, 'successful' => 2, 'failed' => 0],
            ]);

        $bhilwara = Station::query()->where('name', 'Bhilwara Depot')->firstOrFail();
        $this->assertDatabaseHas('company_station_agents', [
            'station_id' => $bhilwara->id,
            'agent_id' => $agent->id,
        ]);
        $this->assertDatabaseHas('stations', ['name' => 'Chittor Depot']);
    }

    public function test_bulk_upload_reports_unknown_state_city_and_agent(): void
    {
        $context = $this->createTenantContext(['create stations']);

        $csv = "Station Name,State,City,Default Agent\n"
            ."Bad State,Atlantis,Udaipur,\n"
            ."Bad City,Rajasthan,Nowhere,\n"
            ."Bad Agent,Rajasthan,Udaipur,Ghost Agent\n"
            ."No City,Rajasthan,,\n";

        $response = $this->actingAs($context['user'])
            ->postJson(route('stations.bulk-upload'), [
                'file' => UploadedFile::fake()->createWithContent('stations.csv', $csv),
            ])
            ->assertOk();

        $this->assertSame(0, $response->json('summary.successful'));
        $messages = collect($response->json('errors'))->pluck('message')->implode(' | ');
        $this->assertStringContainsString('State "Atlantis" was not found', $messages);
        $this->assertStringContainsString('City "Nowhere" was not found', $messages);
        $this->assertStringContainsString('Agent "Ghost Agent" not found', $messages);
        $this->assertDatabaseMissing('stations', ['name' => 'Bad State']);
        $this->assertDatabaseMissing('stations', ['name' => 'Bad Agent']);
    }

    public function test_bulk_upload_rejects_invalid_file(): void
    {
        $context = $this->createTenantContext(['create stations']);

        $this->actingAs($context['user'])
            ->postJson(route('stations.bulk-upload'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->actingAs($context['user'])
            ->postJson(route('stations.bulk-upload'), [
                'file' => UploadedFile::fake()->create('stations.pdf', 10, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_bulk_upload_requires_permission(): void
    {
        $context = $this->createTenantContext(['view stations']);

        $this->actingAs($context['user'])
            ->postJson(route('stations.bulk-upload'), [
                'file' => UploadedFile::fake()->createWithContent('stations.csv', "Station Name,State,City\n"),
            ])
            ->assertForbidden();
    }

    public function test_stations_of_other_owner_are_not_visible(): void
    {
        $context = $this->createTenantContext(['view stations']);
        $other = $this->createTenantContext([], 'B');

        $this->actingAs($context['user'])
            ->get(route('stations.show', $other['station']))
            ->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeStation(array $context, string $name): Station
    {
        $city = City::query()->firstOrCreate(
            ['name' => 'Jaipur', 'state_id' => $context['state']->id],
            ['country_id' => $context['country']->id]
        );

        return Station::create([
            'name' => $name,
            'owner_id' => $context['owner']->id,
            'state_id' => $context['state']->id,
            'city_id' => $city->id,
            'country_id' => $context['country']->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeAgent(array $context): Agent
    {
        return Agent::create([
            'company_id' => $context['company']->id,
            'name' => 'Quick Agent',
            'address' => '1 Agent Street',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9876543210',
        ]);
    }
}
