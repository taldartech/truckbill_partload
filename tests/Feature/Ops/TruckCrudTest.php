<?php

namespace Tests\Feature\Ops;

use App\Models\Agent;
use App\Models\City;
use App\Models\State;
use App\Models\Truck;
use App\Models\TruckType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class TruckCrudTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    private const ALL_PERMISSIONS = [
        'view trucks',
        'create trucks',
        'edit trucks',
        'delete trucks',
    ];

    public function test_index_lists_company_trucks_only(): void
    {
        $context = $this->truckContext(self::ALL_PERMISSIONS);
        $other = $this->truckContext(self::ALL_PERMISSIONS, 'B');
        $this->makeTruck($context, 'RJ27AA0001');
        $this->makeTruck($context, 'RJ27AA0002');
        $this->makeTruck($other, 'RJ27ZZ9999');

        $this->actingAs($context['user'])
            ->get(route('trucks.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trucks/Index')
                ->has('trucks.data', 2)
                ->has('states')
                ->has('cities')
                ->has('stations')
                ->where('filters.search', '')
            );
    }

    public function test_index_searches_by_number_status_and_agent_name(): void
    {
        $context = $this->truckContext(self::ALL_PERMISSIONS);
        $this->makeTruck($context, 'RJ27AA0001', ['status' => 'Available']);
        $this->makeTruck($context, 'RJ27BB0002', ['status' => 'In Transit']);
        $this->makeTruck($context, 'RJ27CC0003', ['status' => 'Booked', 'agent_id' => $context['agent']->id]);

        $this->actingAs($context['user'])
            ->get(route('trucks.index', ['search' => 'BB0002']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('trucks.data', 1)
                ->where('trucks.data.0.truck_number', 'RJ27BB0002')
                ->where('filters.search', 'BB0002')
            );

        $this->actingAs($context['user'])
            ->get(route('trucks.index', ['search' => 'In Transit']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('trucks.data', 1)
                ->where('trucks.data.0.truck_number', 'RJ27BB0002')
            );

        $this->actingAs($context['user'])
            ->get(route('trucks.index', ['search' => 'Owner Agent']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('trucks.data', 1)
                ->where('trucks.data.0.truck_number', 'RJ27CC0003')
                ->where('trucks.data.0.agent.name', 'Owner Agent')
            );

        $this->actingAs($context['user'])
            ->get(route('trucks.index', ['search' => 'nothing-matches']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('trucks.data', 0));
    }

    public function test_index_validates_search_length_and_requires_permission(): void
    {
        $context = $this->truckContext(['view trucks']);

        $this->actingAs($context['user'])
            ->from(route('trucks.index'))
            ->get(route('trucks.index', ['search' => str_repeat('a', 101)]))
            ->assertSessionHasErrors('search');

        $noPermission = $this->truckContext([], 'B');
        $this->actingAs($noPermission['user'])->get(route('trucks.index'))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('trucks.index'))->assertRedirect(route('login'));
        $this->post(route('trucks.store'), [])->assertRedirect(route('login'));
    }

    public function test_show_renders_truck_with_relations(): void
    {
        $context = $this->truckContext(['view trucks']);
        $truck = $this->makeTruck($context, 'RJ27AA0001', ['agent_id' => $context['agent']->id]);

        $this->actingAs($context['user'])
            ->get(route('trucks.show', $truck))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trucks/Show')
                ->where('truck.id', $truck->id)
                ->where('truck.agent.name', 'Owner Agent')
                ->where('truck.agent.state.name', 'Rajasthan')
                ->where('truck.truck_type.name', 'Open Body')
            );
    }

    public function test_show_requires_permission_and_hides_other_tenants(): void
    {
        $context = $this->truckContext(['create trucks']);
        $truck = $this->makeTruck($context, 'RJ27AA0001');
        $this->actingAs($context['user'])->get(route('trucks.show', $truck))->assertForbidden();

        $viewer = $this->truckContext(['view trucks'], 'B');
        $this->actingAs($viewer['user'])->get(route('trucks.show', $truck))->assertNotFound();
    }

    public function test_create_page_renders_lookup_data(): void
    {
        $context = $this->truckContext(['create trucks']);

        $this->actingAs($context['user'])
            ->get(route('trucks.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trucks/Create')
                ->has('truckTypes', 1)
                ->has('agents', 1)
                ->has('states')
                ->has('cities')
                ->has('stations')
            );
    }

    public function test_create_page_requires_permission(): void
    {
        $context = $this->truckContext(['view trucks']);

        $this->actingAs($context['user'])->get(route('trucks.create'))->assertForbidden();
    }

    public function test_store_creates_truck_with_uppercase_number_in_active_company(): void
    {
        $context = $this->truckContext(['create trucks']);

        $this->actingAs($context['user'])
            ->post(route('trucks.store'), [
                'truck_number' => 'rj27ab1234',
                'truck_type_id' => $context['truckType']->id,
                'agent_id' => $context['agent']->id,
                'status' => 'Available',
                'current_state_id' => $context['state']->id,
                'current_city_id' => $context['city']->id,
                'current_station_id' => $context['station']->id,
                'capacity' => '15 Tons',
                'rc_no' => 'RC1',
                'rc_expiry_date' => '2030-01-31',
                'insurance_no' => 'INS1',
                'insurance_expiry_date' => '2030-02-01',
                'fitness_no' => 'FIT1',
                'fitness_expiry_date' => '2030-03-01',
                'pollution_no' => 'PUC1',
                'pollution_expiry_date' => '2030-04-01',
                'permit_no' => 'PER1',
                'permit_expiry_date' => '2030-05-01',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('trucks.index'))
            ->assertSessionHas('success', 'Truck created successfully.');

        $this->assertDatabaseHas('trucks', [
            'company_id' => $context['company']->id,
            'truck_number' => 'RJ27AB1234',
            'truck_type_id' => $context['truckType']->id,
            'agent_id' => $context['agent']->id,
            'status' => 'Available',
            'capacity' => '15 Tons',
            'rc_no' => 'RC1',
            'permit_no' => 'PER1',
        ]);
    }

    public function test_store_ignores_tenant_fields_supplied_by_client(): void
    {
        $context = $this->truckContext(['create trucks']);
        $other = $this->truckContext([], 'B');

        $this->actingAs($context['user'])
            ->post(route('trucks.store'), [
                'truck_number' => 'RJ27AB1111',
                'company_id' => $other['company']->id,
                'branch_id' => 999,
                'owner_id' => $other['owner']->id,
            ])
            ->assertSessionHasNoErrors();

        $truck = Truck::query()->where('truck_number', 'RJ27AB1111')->firstOrFail();
        $this->assertSame($context['company']->id, $truck->company_id);
    }

    public function test_store_accepts_empty_payload_since_all_fields_are_optional(): void
    {
        $context = $this->truckContext(['create trucks']);

        $this->actingAs($context['user'])
            ->post(route('trucks.store'), [])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('trucks.index'));

        $this->assertDatabaseCount('trucks', 1);
    }

    public function test_store_validates_field_rules(): void
    {
        $context = $this->truckContext(['create trucks']);

        $this->actingAs($context['user'])
            ->from(route('trucks.create'))
            ->post(route('trucks.store'), [
                'truck_number' => str_repeat('A', 21),
                'truck_type_id' => 999999,
                'agent_id' => 999999,
                'current_state_id' => 999999,
                'current_city_id' => 999999,
                'current_station_id' => 999999,
                'status' => str_repeat('s', 51),
                'capacity' => str_repeat('c', 51),
                'rc_expiry_date' => 'not-a-date',
                'permit_expiry_date' => 'also-bad',
            ])
            ->assertRedirect(route('trucks.create'))
            ->assertSessionHasErrors([
                'truck_number',
                'truck_type_id',
                'agent_id',
                'current_state_id',
                'current_city_id',
                'current_station_id',
                'status',
                'capacity',
                'rc_expiry_date',
                'permit_expiry_date',
            ]);

        $this->assertDatabaseCount('trucks', 0);
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->truckContext(['view trucks']);

        $this->actingAs($context['user'])
            ->post(route('trucks.store'), ['truck_number' => 'RJ27AB0000'])
            ->assertForbidden();

        $this->assertDatabaseCount('trucks', 0);
    }

    public function test_quick_form_data_returns_truck_types_and_agents(): void
    {
        $context = $this->truckContext(['create trucks']);

        $this->actingAs($context['user'])
            ->getJson(route('trucks.quick-form-data'))
            ->assertOk()
            ->assertJsonCount(1, 'truckTypes')
            ->assertJsonCount(1, 'agents')
            ->assertJsonPath('truckTypes.0.name', 'Open Body')
            ->assertJsonPath('agents.0.name', 'Owner Agent');
    }

    public function test_quick_form_data_requires_permission(): void
    {
        $context = $this->truckContext(['view trucks']);

        $this->actingAs($context['user'])->getJson(route('trucks.quick-form-data'))->assertForbidden();
    }

    public function test_quick_store_creates_truck_and_returns_select_option(): void
    {
        $context = $this->truckContext(['create trucks']);

        $response = $this->actingAs($context['user'])
            ->postJson(route('trucks.quick-store'), [
                'truck_number' => 'gj01xy5555',
                'truck_type_id' => $context['truckType']->id,
                'agent_id' => $context['agent']->id,
            ]);

        $truck = Truck::query()->where('truck_number', 'GJ01XY5555')->firstOrFail();
        $response->assertCreated()
            ->assertExactJson(['truck' => ['id' => $truck->id, 'name' => 'GJ01XY5555']]);
        $this->assertSame($context['company']->id, $truck->company_id);
        $this->assertSame($context['agent']->id, $truck->agent_id);
    }

    public function test_quick_store_validates_input(): void
    {
        $context = $this->truckContext(['create trucks']);

        $this->actingAs($context['user'])
            ->postJson(route('trucks.quick-store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('truck_number');

        $this->actingAs($context['user'])
            ->postJson(route('trucks.quick-store'), [
                'truck_number' => str_repeat('A', 21),
                'truck_type_id' => 999999,
                'agent_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['truck_number', 'truck_type_id', 'agent_id']);

        $this->assertDatabaseCount('trucks', 0);
    }

    public function test_quick_store_requires_permission(): void
    {
        $context = $this->truckContext(['view trucks']);

        $this->actingAs($context['user'])
            ->postJson(route('trucks.quick-store'), ['truck_number' => 'RJ27AB0000'])
            ->assertForbidden();
    }

    public function test_edit_page_renders_truck_and_lookups(): void
    {
        $context = $this->truckContext(['edit trucks']);
        $truck = $this->makeTruck($context, 'RJ27AA0001');

        $this->actingAs($context['user'])
            ->get(route('trucks.edit', $truck))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trucks/Edit')
                ->where('truck.id', $truck->id)
                ->has('truckTypes', 1)
                ->has('agents', 1)
                ->has('states')
                ->has('cities')
                ->has('stations')
            );
    }

    public function test_edit_page_requires_permission(): void
    {
        $context = $this->truckContext(['view trucks']);
        $truck = $this->makeTruck($context, 'RJ27AA0001');

        $this->actingAs($context['user'])->get(route('trucks.edit', $truck))->assertForbidden();
    }

    public function test_update_changes_truck_and_uppercases_number(): void
    {
        $context = $this->truckContext(['edit trucks']);
        $truck = $this->makeTruck($context, 'RJ27AA0001');

        $this->actingAs($context['user'])
            ->put(route('trucks.update', $truck), [
                'truck_number' => 'rj27zz7777',
                'agent_id' => $context['agent']->id,
                'status' => 'Maintenance',
                'capacity' => '20 Tons',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('trucks.index'))
            ->assertSessionHas('success', 'Truck updated successfully.');

        $truck->refresh();
        $this->assertSame('RJ27ZZ7777', $truck->truck_number);
        $this->assertSame($context['agent']->id, $truck->agent_id);
        $this->assertSame('Maintenance', $truck->status);
        $this->assertSame('20 Tons', $truck->capacity);
    }

    public function test_update_validates_input_and_requires_permission(): void
    {
        $context = $this->truckContext(['edit trucks']);
        $truck = $this->makeTruck($context, 'RJ27AA0001');

        $this->actingAs($context['user'])
            ->put(route('trucks.update', $truck), ['truck_number' => str_repeat('A', 21), 'agent_id' => 999999])
            ->assertSessionHasErrors(['truck_number', 'agent_id']);
        $this->assertSame('RJ27AA0001', $truck->fresh()->truck_number);

        $viewer = $this->truckContext(['view trucks'], 'B');
        $viewerTruck = $this->makeTruck($viewer, 'RJ27BB0001');
        $this->actingAs($viewer['user'])
            ->put(route('trucks.update', $viewerTruck), ['truck_number' => 'RJ27BB0002'])
            ->assertForbidden();
    }

    public function test_update_cannot_touch_other_tenants_truck(): void
    {
        $context = $this->truckContext(['edit trucks']);
        $other = $this->truckContext([], 'B');
        $foreign = $this->makeTruck($other, 'RJ27ZZ0001');

        $this->actingAs($context['user'])
            ->put(route('trucks.update', $foreign), ['truck_number' => 'HACKED'])
            ->assertNotFound();

        $this->assertSame('RJ27ZZ0001', $foreign->fresh()->truck_number);
    }

    public function test_destroy_soft_deletes_truck_with_remark(): void
    {
        $context = $this->truckContext(['delete trucks']);
        $truck = $this->makeTruck($context, 'RJ27AA0001');

        $this->actingAs($context['user'])
            ->delete(route('trucks.destroy', $truck), ['delete_remark' => 'Sold'])
            ->assertRedirect(route('trucks.index'))
            ->assertSessionHas('success', 'Truck deleted successfully.');

        $this->assertSoftDeleted('trucks', ['id' => $truck->id]);
        $this->assertDatabaseHas('trucks', [
            'id' => $truck->id,
            'delete_remark' => 'Sold',
            'deleted_by' => $context['user']->id,
        ]);
    }

    public function test_destroy_requires_permission(): void
    {
        $context = $this->truckContext(['view trucks', 'edit trucks']);
        $truck = $this->makeTruck($context, 'RJ27AA0001');

        $this->actingAs($context['user'])->delete(route('trucks.destroy', $truck))->assertForbidden();

        $this->assertNotSoftDeleted('trucks', ['id' => $truck->id]);
    }

    public function test_complete_tour_updates_status_and_location(): void
    {
        $context = $this->truckContext([]);
        $truck = $this->makeTruck($context, 'RJ27AA0001', ['status' => 'In Transit']);

        $this->actingAs($context['user'])
            ->from(route('trucks.index'))
            ->put(route('trucks.complete-tour', $truck), [
                'status' => 'Available',
                'current_state_id' => $context['state']->id,
                'current_city_id' => $context['city']->id,
                'current_station_id' => $context['station']->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('trucks.index'))
            ->assertSessionHas('success', 'Truck status and location updated successfully.');

        $truck->refresh();
        $this->assertSame('Available', $truck->status);
        $this->assertSame($context['state']->id, $truck->current_state_id);
        $this->assertSame($context['city']->id, $truck->current_city_id);
        $this->assertSame($context['station']->id, $truck->current_station_id);
    }

    public function test_complete_tour_allows_clearing_station(): void
    {
        $context = $this->truckContext([]);
        $truck = $this->makeTruck($context, 'RJ27AA0001', ['current_station_id' => $context['station']->id]);

        $this->actingAs($context['user'])
            ->put(route('trucks.complete-tour', $truck), [
                'status' => 'Available',
                'current_state_id' => $context['state']->id,
                'current_city_id' => $context['city']->id,
                'current_station_id' => null,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($truck->fresh()->current_station_id);
    }

    public function test_complete_tour_validates_input(): void
    {
        $context = $this->truckContext([]);
        $truck = $this->makeTruck($context, 'RJ27AA0001', ['status' => 'In Transit']);

        $this->actingAs($context['user'])
            ->put(route('trucks.complete-tour', $truck), [])
            ->assertSessionHasErrors(['status', 'current_state_id', 'current_city_id']);

        $this->actingAs($context['user'])
            ->put(route('trucks.complete-tour', $truck), [
                'status' => 'Available',
                'current_state_id' => 999999,
                'current_city_id' => 999999,
                'current_station_id' => 999999,
            ])
            ->assertSessionHasErrors(['current_state_id', 'current_city_id', 'current_station_id']);

        $this->assertSame('In Transit', $truck->fresh()->status);
    }

    public function test_complete_tour_is_not_available_for_other_tenants_trucks(): void
    {
        $context = $this->truckContext([]);
        $other = $this->truckContext([], 'B');
        $foreign = $this->makeTruck($other, 'RJ27ZZ0001', ['status' => 'In Transit']);

        $this->actingAs($context['user'])
            ->put(route('trucks.complete-tour', $foreign), [
                'status' => 'Available',
                'current_state_id' => $context['state']->id,
                'current_city_id' => $context['city']->id,
            ])
            ->assertNotFound();

        $this->assertSame('In Transit', $foreign->fresh()->status);
    }

    public function test_bulk_upload_requires_a_csv_or_excel_file(): void
    {
        $context = $this->truckContext(['create trucks']);

        $this->actingAs($context['user'])
            ->postJson(route('trucks.bulk-upload'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->actingAs($context['user'])
            ->postJson(route('trucks.bulk-upload'), [
                'file' => UploadedFile::fake()->createWithContent('trucks.txt', "Truck Number\nRJ27AB0001"),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_bulk_upload_reports_unresolvable_lookup_values(): void
    {
        $context = $this->truckContext(['create trucks']);

        $csv = "Truck Number,Truck Type,Status,Truck Owner,State,City,Station\n"
            ."RJ27AB0001,Unknown Type,Available,,,,\n"
            ."RJ27AB0002,,Flying,,,,\n"
            ."RJ27AB0003,,,Nobody Agent,,,\n"
            ."RJ27AB0004,,,,Atlantis,,\n"
            ."RJ27AB0005,,,,,Udaipur,\n"
            ."RJ27AB0006,,,,,,Missing Station\n";

        $response = $this->uploadTrucks($context, $csv);

        $response->assertOk()->assertJson([
            'success' => false,
            'summary' => ['total' => 6, 'successful' => 0, 'failed' => 6],
        ]);
        $fields = collect($response->json('errors'))->pluck('field')->all();
        foreach (['Truck Type', 'Status', 'Truck Owner', 'State', 'City', 'Station'] as $field) {
            $this->assertContains($field, $fields);
        }
        $this->assertDatabaseCount('trucks', 0);
    }

    public function test_bulk_upload_resolves_lookups_by_code_id_and_case_insensitive_names(): void
    {
        $context = $this->truckContext(['create trucks']);

        $csv = "Truck Number,Truck Type,Status,Truck Owner,State,City,Station,Capacity,RC Expiry Date\n"
            ."rj27ab0001,open body,in transit,owner agent,RJ,udaipur,Station A,,\n"
            ."rj27ab0002,{$context['truckType']->id},,{$context['agent']->id},{$context['state']->id},{$context['city']->id},{$context['station']->id},12 Tons,2031-01-01\n"
            ."rj27ab0003,,,,{$context['state']->id},udaipur,,,\n";

        $response = $this->uploadTrucks($context, $csv);

        $response->assertOk()->assertJson([
            'success' => true,
            'summary' => ['total' => 3, 'successful' => 3, 'failed' => 0],
        ]);

        $first = Truck::query()->where('truck_number', 'RJ27AB0001')->firstOrFail();
        $this->assertSame('In Transit', $first->status);
        $this->assertSame($context['truckType']->id, $first->truck_type_id);
        $this->assertSame($context['agent']->id, $first->agent_id);
        $this->assertSame($context['state']->id, $first->current_state_id);
        $this->assertSame($context['city']->id, $first->current_city_id);
        $this->assertSame($context['station']->id, $first->current_station_id);
        $this->assertNull($first->capacity);
        $this->assertNull($first->rc_expiry_date);

        $second = Truck::query()->where('truck_number', 'RJ27AB0002')->firstOrFail();
        $this->assertSame('Available', $second->status);
        $this->assertSame('12 Tons', $second->capacity);
        $this->assertSame($context['agent']->id, $second->agent_id);

        $third = Truck::query()->where('truck_number', 'RJ27AB0003')->firstOrFail();
        $this->assertSame($context['city']->id, $third->current_city_id);
    }

    public function test_bulk_upload_rejects_city_that_belongs_to_another_state(): void
    {
        $context = $this->truckContext(['create trucks']);
        $otherState = State::create([
            'country_id' => $context['country']->id,
            'name' => 'Gujarat',
            'code' => 'GJ',
        ]);
        City::create(['name' => 'Surat', 'state_id' => $otherState->id, 'country_id' => $context['country']->id]);

        $csv = "Truck Number,State,City\n"
            ."RJ27AB0001,Rajasthan,Surat\n";

        $response = $this->uploadTrucks($context, $csv);

        $response->assertOk()->assertJson(['success' => false]);
        $this->assertStringContainsString('was not found in city records', $response->json('errors.0.message'));
    }

    public function test_bulk_upload_template_requires_permission(): void
    {
        $context = $this->truckContext(['view trucks']);

        $this->actingAs($context['user'])->get(route('trucks.bulk-upload.template'))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function uploadTrucks(array $context, string $csv): TestResponse
    {
        return $this->actingAs($context['user'])->post(
            route('trucks.bulk-upload'),
            ['file' => UploadedFile::fake()->createWithContent('trucks.csv', $csv)]
        );
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    private function truckContext(array $permissions, string $suffix = 'A'): array
    {
        $context = $this->createTenantContext($permissions, $suffix);

        $context['truckType'] = TruckType::create([
            'owner_id' => $context['owner']->id,
            'name' => 'Open Body',
        ]);
        $context['agent'] = Agent::create([
            'company_id' => $context['company']->id,
            'name' => 'Owner Agent',
            'address' => '1 Agent Street',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9876543210',
        ]);

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $attributes
     */
    private function makeTruck(array $context, string $number, array $attributes = []): Truck
    {
        return Truck::create(array_merge([
            'company_id' => $context['company']->id,
            'truck_number' => $number,
            'truck_type_id' => $context['truckType']->id,
            'status' => 'Available',
        ], $attributes));
    }
}
