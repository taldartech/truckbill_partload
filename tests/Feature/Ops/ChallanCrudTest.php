<?php

namespace Tests\Feature\Ops;

use App\Models\Agent;
use App\Models\Bilty;
use App\Models\Challan;
use App\Models\ChallanDetail;
use App\Models\MainChallan;
use App\Models\MainChallanDetail;
use App\Models\Party;
use App\Models\Station;
use App\Models\Truck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class ChallanCrudTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    private const ALL_PERMISSIONS = [
        'view challans',
        'create challans',
        'edit challans',
        'delete challans',
    ];

    public function test_create_page_renders_with_next_number_and_pending_bilties(): void
    {
        $context = $this->createChallanContext(['create challans']);

        $this->actingAs($context['user'])
            ->get(route('challans.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Challans/Create')
                ->has('stations')
                ->has('trucks', 1)
                ->has('bilties', 1)
                ->has('agents')
                ->has('next_challan_number')
            );
    }

    public function test_create_page_requires_permission(): void
    {
        $context = $this->createChallanContext(['view challans']);

        $this->actingAs($context['user'])
            ->get(route('challans.create'))
            ->assertForbidden();
    }

    public function test_store_creates_challan_and_marks_bilty_in_transit(): void
    {
        $context = $this->createChallanContext(['create challans']);

        $response = $this->actingAs($context['user'])
            ->post(route('challans.store'), $this->validPayload($context));

        $response->assertSessionHasNoErrors()->assertRedirect(route('challans.index'));

        $challan = Challan::query()->where('driver_name', 'Ramesh')->firstOrFail();
        $this->assertSame($context['company']->id, $challan->company_id);
        $this->assertSame($context['branch']->id, $challan->branch_id);
        $this->assertSame($context['user']->id, $challan->created_by);
        $this->assertEqualsWithDelta(500.0, (float) $challan->booking_freight, 0.001);

        $this->assertDatabaseHas('challan_details', [
            'challan_id' => $challan->id,
            'bilty_id' => $context['bilty']->id,
        ]);
        $this->assertSame('In Transit', $context['bilty']->fresh()->status);
    }

    public function test_store_with_print_after_save_flashes_print_url(): void
    {
        $context = $this->createChallanContext(['create challans']);

        $response = $this->actingAs($context['user'])
            ->post(route('challans.store'), $this->validPayload($context) + ['print_after_save' => true]);

        $challan = Challan::query()->firstOrFail();
        $response->assertRedirect(route('challans.index'))
            ->assertSessionHas('print_url', route('challans.print', $challan));
    }

    public function test_store_syncs_pay_by_agent_name(): void
    {
        $context = $this->createChallanContext(['create challans']);

        $this->actingAs($context['user'])
            ->post(route('challans.store'), $this->validPayload($context, [
                'pay_by_agent_id' => $context['agent']->id,
                'delivery_at_agent_id' => $context['agent']->id,
            ]))
            ->assertSessionHasNoErrors();

        $challan = Challan::query()->firstOrFail();
        $this->assertSame('Test Agent', $challan->pay_by);
        $this->assertSame('Test Agent', $challan->delivery_at);
        $this->assertSame($context['agent']->id, $challan->pay_by_agent_id);
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->createChallanContext(['view challans']);

        $this->actingAs($context['user'])
            ->post(route('challans.store'), $this->validPayload($context))
            ->assertForbidden();

        $this->assertDatabaseCount('challans', 0);
    }

    public function test_store_validates_required_fields(): void
    {
        $context = $this->createChallanContext(['create challans']);

        $this->actingAs($context['user'])
            ->from(route('challans.create'))
            ->post(route('challans.store'), [])
            ->assertRedirect(route('challans.create'))
            ->assertSessionHasErrors([
                'from_station_id',
                'to_station_id',
                'driver_name',
                'truck_id',
                'details',
            ]);
    }

    public function test_store_rejects_invalid_references_and_row_type(): void
    {
        $context = $this->createChallanContext(['create challans']);

        $this->actingAs($context['user'])
            ->post(route('challans.store'), $this->validPayload($context, [
                'truck_id' => 999999,
                'from_station_id' => 999999,
                'details' => [['row_type' => 'bogus', 'bilty_id' => 999999]],
            ]))
            ->assertSessionHasErrors([
                'truck_id',
                'from_station_id',
                'details.0.row_type',
                'details.0.bilty_id',
            ]);
    }

    public function test_store_rejects_pay_by_agent_from_another_company(): void
    {
        $context = $this->createChallanContext(['create challans']);
        $other = $this->createTenantContext([], 'B');
        $foreignAgent = Agent::create([
            'company_id' => $other['company']->id,
            'name' => 'Foreign Agent',
            'address' => '9 Other Street',
            'state_id' => $other['state']->id,
            'city_id' => $other['city']->id,
            'mobile' => '9876500000',
        ]);

        $this->actingAs($context['user'])
            ->post(route('challans.store'), $this->validPayload($context, [
                'pay_by_agent_id' => $foreignAgent->id,
            ]))
            ->assertSessionHasErrors('pay_by_agent_id');

        $this->assertDatabaseCount('challans', 0);
    }

    public function test_store_rejects_non_numeric_amounts_and_bad_date(): void
    {
        $context = $this->createChallanContext(['create challans']);

        $this->actingAs($context['user'])
            ->post(route('challans.store'), $this->validPayload($context, [
                'labour_charge' => 'abc',
                'total_amount' => 'xyz',
                'date' => 'not-a-date',
            ]))
            ->assertSessionHasErrors(['labour_charge', 'total_amount', 'date']);
    }

    public function test_store_rejects_detail_row_without_reference(): void
    {
        $context = $this->createChallanContext(['create challans']);

        $this->actingAs($context['user'])
            ->post(route('challans.store'), $this->validPayload($context, [
                'details' => [['row_type' => 'bilty']],
            ]))
            ->assertSessionHasErrors('details.0');

        $this->assertDatabaseCount('challans', 0);
    }

    public function test_store_rejects_detail_row_with_both_references(): void
    {
        $context = $this->createChallanContext(['create challans']);

        $this->actingAs($context['user'])
            ->post(route('challans.store'), $this->validPayload($context, [
                'details' => [[
                    'row_type' => 'bilty',
                    'bilty_id' => $context['bilty']->id,
                    'inward_register_detail_id' => 999999,
                ]],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('challans', 0);
    }

    public function test_edit_page_renders_for_permitted_user(): void
    {
        $context = $this->createChallanContext(['edit challans']);
        $challan = $this->createExistingChallan($context);

        $this->actingAs($context['user'])
            ->get(route('challans.edit', $challan))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Challans/Edit')
                ->where('challan.id', $challan->id)
                ->has('challan.details', 1)
                ->has('stations')
                ->has('trucks')
                ->has('inward_details')
            );
    }

    public function test_edit_page_requires_permission(): void
    {
        $context = $this->createChallanContext(['view challans']);
        $challan = $this->createExistingChallan($context);

        $this->actingAs($context['user'])
            ->get(route('challans.edit', $challan))
            ->assertForbidden();
    }

    public function test_update_changes_challan_and_resyncs_details(): void
    {
        $context = $this->createChallanContext(['edit challans']);
        $challan = $this->createExistingChallan($context);
        $secondBilty = $this->createBilty($context, 'BLT-2', 750);

        $this->actingAs($context['user'])
            ->put(route('challans.update', $challan), $this->validPayload($context, [
                'driver_name' => 'Updated Driver',
                'details' => [['row_type' => 'bilty', 'bilty_id' => $secondBilty->id]],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('challans.index'));

        $challan->refresh();
        $this->assertSame('Updated Driver', $challan->driver_name);
        $this->assertEqualsWithDelta(750.0, (float) $challan->booking_freight, 0.001);

        $this->assertSame('Pending', $context['bilty']->fresh()->status);
        $this->assertSame('In Transit', $secondBilty->fresh()->status);
        $this->assertSame(
            [$secondBilty->id],
            ChallanDetail::query()->where('challan_id', $challan->id)->pluck('bilty_id')->all()
        );
    }

    public function test_update_with_print_after_save_flashes_print_url(): void
    {
        $context = $this->createChallanContext(['edit challans']);
        $challan = $this->createExistingChallan($context);

        $this->actingAs($context['user'])
            ->put(route('challans.update', $challan), $this->validPayload($context) + ['print_after_save' => '1'])
            ->assertSessionHas('print_url', route('challans.print', $challan));
    }

    public function test_update_requires_permission(): void
    {
        $context = $this->createChallanContext(['view challans']);
        $challan = $this->createExistingChallan($context);

        $this->actingAs($context['user'])
            ->put(route('challans.update', $challan), $this->validPayload($context, ['driver_name' => 'Nope']))
            ->assertForbidden();

        $this->assertSame('Existing Driver', $challan->fresh()->driver_name);
    }

    public function test_update_validates_input(): void
    {
        $context = $this->createChallanContext(['edit challans']);
        $challan = $this->createExistingChallan($context);

        $this->actingAs($context['user'])
            ->put(route('challans.update', $challan), [
                'driver_name' => '',
                'details' => [],
            ])
            ->assertSessionHasErrors(['from_station_id', 'to_station_id', 'driver_name', 'truck_id', 'details']);
    }

    public function test_update_rejects_pay_by_agent_when_main_challan_already_settles_it(): void
    {
        $context = $this->createChallanContext(['edit challans']);
        $challan = $this->createExistingChallan($context);

        $mainChallan = MainChallan::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'main_challan_number' => 'MC-77',
            'date' => now()->toDateString(),
            'from_station_id' => $context['station']->id,
            'to_station_id' => $context['to_station']->id,
            'truck_id' => $context['truck']->id,
            'pay_by_agent_id' => $context['agent']->id,
        ]);
        MainChallanDetail::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'main_challan_id' => $mainChallan->id,
            'challan_id' => $challan->id,
        ]);

        $this->actingAs($context['user'])
            ->put(route('challans.update', $challan), $this->validPayload($context, [
                'pay_by_agent_id' => $context['agent']->id,
            ]))
            ->assertSessionHasErrors('pay_by_agent_id');
    }

    public function test_destroy_soft_deletes_and_resets_bilty_status(): void
    {
        $context = $this->createChallanContext(['delete challans']);
        $challan = $this->createExistingChallan($context);

        $this->actingAs($context['user'])
            ->delete(route('challans.destroy', $challan), ['delete_remark' => 'Entered by mistake'])
            ->assertRedirect(route('challans.index'));

        $this->assertSoftDeleted('challans', ['id' => $challan->id]);
        $this->assertDatabaseHas('challans', [
            'id' => $challan->id,
            'delete_remark' => 'Entered by mistake',
            'deleted_by' => $context['user']->id,
        ]);
        $this->assertSame('Pending', $context['bilty']->fresh()->status);
    }

    public function test_destroy_requires_permission(): void
    {
        $context = $this->createChallanContext(['view challans', 'edit challans']);
        $challan = $this->createExistingChallan($context);

        $this->actingAs($context['user'])
            ->delete(route('challans.destroy', $challan))
            ->assertForbidden();

        $this->assertNotSoftDeleted('challans', ['id' => $challan->id]);
    }

    public function test_print_renders_view_with_challan_data(): void
    {
        $context = $this->createChallanContext(['view challans']);
        $challan = $this->createExistingChallan($context);

        $this->actingAs($context['user'])
            ->get(route('challans.print', $challan))
            ->assertOk()
            ->assertViewIs('prints.challan')
            ->assertViewHas('challan', fn (Challan $viewChallan) => $viewChallan->is($challan));
    }

    public function test_print_is_not_available_to_other_tenants(): void
    {
        $context = $this->createChallanContext(['view challans']);
        $challan = $this->createExistingChallan($context);
        $other = $this->createTenantContext(['view challans'], 'B');

        $this->actingAs($other['user'])
            ->get(route('challans.print', $challan))
            ->assertNotFound();
    }

    public function test_index_lists_challans_and_filters_by_status(): void
    {
        $context = $this->createChallanContext(self::ALL_PERMISSIONS);
        $this->createExistingChallan($context);

        $this->actingAs($context['user'])
            ->get(route('challans.index', ['status' => 'Created']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Challans/Index')
                ->has('challans.data', 1)
                ->where('filters.status', 'Created')
            );

        $this->actingAs($context['user'])
            ->get(route('challans.index', ['status' => 'Completed']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('challans.data', 0));
    }

    public function test_index_validates_date_range(): void
    {
        $context = $this->createChallanContext(['view challans']);

        $this->actingAs($context['user'])
            ->from(route('challans.index'))
            ->get(route('challans.index', ['from_date' => '2026-02-10', 'to_date' => '2026-02-01']))
            ->assertSessionHasErrors('to_date');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('challans.index'))->assertRedirect(route('login'));
        $this->post(route('challans.store'), [])->assertRedirect(route('login'));
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    private function createChallanContext(array $permissions): array
    {
        $context = $this->createTenantContext($permissions);

        $context['to_station'] = Station::create([
            'name' => 'Destination Station',
            'owner_id' => $context['owner']->id,
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'country_id' => $context['country']->id,
        ]);

        $context['truck'] = Truck::create([
            'company_id' => $context['company']->id,
            'truck_number' => 'RJ27AB1234',
        ]);

        $context['agent'] = Agent::create([
            'company_id' => $context['company']->id,
            'name' => 'Test Agent',
            'address' => '1 Agent Street',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9876543210',
        ]);

        $context['bilty'] = $this->createBilty($context, 'BLT-1', 500);

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function createBilty(array $context, string $number, float $freight): Bilty
    {
        $consignor = Party::query()->firstOrCreate(
            ['company_id' => $context['company']->id, 'name' => 'Consignor'],
            ['state_id' => $context['state']->id, 'city_id' => $context['city']->id]
        );
        $consignee = Party::query()->firstOrCreate(
            ['company_id' => $context['company']->id, 'name' => 'Consignee'],
            ['state_id' => $context['state']->id, 'city_id' => $context['city']->id]
        );

        return Bilty::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_number' => $number,
            'date' => now()->toDateString(),
            'from_station_id' => $context['station']->id,
            'to_station_id' => $context['to_station']->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'freight' => $freight,
            'status' => 'Pending',
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $context, array $overrides = []): array
    {
        return array_merge([
            'from_station_id' => $context['station']->id,
            'to_station_id' => $context['to_station']->id,
            'driver_name' => 'Ramesh',
            'truck_id' => $context['truck']->id,
            'date' => now()->toDateString(),
            'status' => 'Created',
            'details' => [[
                'row_type' => 'bilty',
                'bilty_id' => $context['bilty']->id,
            ]],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function createExistingChallan(array $context): Challan
    {
        $challan = Challan::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'challan_number' => 'CH-EXIST-1',
            'date' => now()->toDateString(),
            'from_station_id' => $context['station']->id,
            'to_station_id' => $context['to_station']->id,
            'truck_id' => $context['truck']->id,
            'driver_name' => 'Existing Driver',
            'booking_freight' => 500,
            'total_amount' => 500,
            'status' => 'Created',
            'created_by' => $context['user']->id,
        ]);

        ChallanDetail::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'challan_id' => $challan->id,
            'bilty_id' => $context['bilty']->id,
            'to_station_id' => $context['to_station']->id,
        ]);

        $context['bilty']->update(['status' => 'In Transit']);

        return $challan;
    }
}
