<?php

namespace Tests\Feature\Ops;

use App\Models\Agent;
use App\Models\Good;
use App\Models\InwardRegister;
use App\Models\InwardRegisterDetail;
use App\Models\Party;
use App\Models\Station;
use App\Models\Truck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class InwardRegisterCrudTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    private const ALL_PERMISSIONS = [
        'view inward_registers',
        'create inward_registers',
        'edit inward_registers',
        'delete inward_registers',
    ];

    public function test_index_lists_registers_with_default_filters(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);
        $older = $this->makeRegister($context, 'IR-OLD-1', ['date' => now()->startOfMonth()->toDateString()]);
        $latest = $this->makeRegister($context, 'IR-NEW-2');

        $this->actingAs($context['user'])
            ->get(route('inward-registers.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('InwardRegisters/Index')
                ->has('registers.data', 2)
                ->where('registers.data.0.id', $latest->id)
                ->where('registers.data.1.id', $older->id)
                ->has('registers.data.0.details', 1)
                ->has('stations', 2)
                ->has('trucks', 1)
                ->where('trucks.0.name', 'MH-01-0001')
                ->where('filters.search', '')
                ->where('filters.from_date', now()->startOfMonth()->toDateString())
                ->where('filters.to_date', now()->endOfMonth()->toDateString())
                ->where('filters.from_station_id', '')
                ->where('filters.truck_id', '')
            );
    }

    public function test_index_filters_by_search_station_truck_and_dates(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);
        $otherTruck = Truck::create(['company_id' => $context['company']->id, 'truck_number' => 'GJ-09-9999']);
        $otherStation = Station::create(['name' => 'Elsewhere', 'owner_id' => $context['owner']->id]);

        $this->makeRegister($context, 'IR-ALPHA-1');
        $this->makeRegister($context, 'IR-BETA-2', ['challan_number' => 'CH-SPECIAL']);
        $this->makeRegister($context, 'IR-GAMMA-3', ['driver_name' => 'Zorro Driver']);
        $this->makeRegister($context, 'IR-DELTA-4', ['truck_id' => $otherTruck->id]);
        $this->makeRegister($context, 'IR-EPS-5', ['from_station_id' => $otherStation->id]);
        $this->makeRegister($context, 'IR-ANCIENT-6', ['date' => now()->subMonths(3)->toDateString()]);

        $names = fn (array $query) => collect(
            $this->actingAs($context['user'])
                ->get(route('inward-registers.index', $query))
                ->assertOk()
                ->viewData('page')['props']['registers']['data']
        )->pluck('inward_register_no')->sort()->values()->all();

        $this->assertSame(['IR-ALPHA-1'], $names(['search' => 'ALPHA']));
        $this->assertSame(['IR-BETA-2'], $names(['search' => 'CH-SPECIAL']));
        $this->assertSame(['IR-GAMMA-3'], $names(['search' => 'Zorro']));
        $this->assertSame(['IR-DELTA-4'], $names(['search' => 'GJ-09']));
        $this->assertSame(['IR-DELTA-4'], $names(['truck_id' => $otherTruck->id]));
        $this->assertSame(['IR-EPS-5'], $names(['from_station_id' => $otherStation->id]));
        $this->assertSame(['IR-ANCIENT-6'], $names([
            'from_date' => now()->subMonths(4)->toDateString(),
            'to_date' => now()->subMonths(2)->toDateString(),
        ]));
        $this->assertCount(5, $names([]));
    }

    public function test_index_echoes_selected_filters(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);

        $this->actingAs($context['user'])
            ->get(route('inward-registers.index', [
                'from_station_id' => $context['fromStation']->id,
                'truck_id' => $context['truck']->id,
            ]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.from_station_id', (string) $context['fromStation']->id)
                ->where('filters.truck_id', (string) $context['truck']->id)
            );
    }

    public function test_index_rejects_invalid_filters(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);

        $this->actingAs($context['user'])
            ->from(route('inward-registers.index'))
            ->get(route('inward-registers.index', [
                'from_date' => '2026-05-10',
                'to_date' => '2026-05-01',
                'from_station_id' => 999999,
                'truck_id' => 999999,
            ]))
            ->assertSessionHasErrors(['to_date', 'from_station_id', 'truck_id']);
    }

    public function test_index_only_shows_registers_of_own_company(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);
        $this->makeRegister($context, 'IR-MINE-1');

        $other = $this->registerContext([], 'B');
        $this->makeRegister($other, 'IR-THEIRS-1');

        $this->actingAs($context['user'])
            ->get(route('inward-registers.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('registers.data', 1)
                ->where('registers.data.0.inward_register_no', 'IR-MINE-1')
            );
    }

    public function test_create_page_renders_lookups_and_next_number(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);

        $this->actingAs($context['user'])
            ->get(route('inward-registers.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('InwardRegisters/Create')
                ->has('stations', 2)
                ->has('trucks', 1)
                ->has('goods', 1)
                ->has('parties', 2)
                ->has('agents', 1)
                ->has('next_inward_register_no')
            );
    }

    public function test_store_creates_register_with_details_and_totals(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);

        $this->actingAs($context['user'])
            ->post(route('inward-registers.store'), $this->payload($context, [
                'total_packages' => 9999,
                'details' => [
                    $this->detailPayload($context, [
                        'gr_no' => 'GR-DELIVER',
                        'to_station_id' => $context['fromStation']->id,
                        'no_of_packages' => 4,
                        'freight' => 400,
                    ]),
                    $this->detailPayload($context, [
                        'gr_no' => 'GR-FORWARD',
                        'to_station_id' => $context['toStation']->id,
                        'no_of_packages' => 6,
                        'freight' => 250.5,
                    ]),
                ],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('inward-registers.index'))
            ->assertSessionHas('success', 'Inward Register created successfully.');

        $register = InwardRegister::query()->where('inward_register_no', 'IR-NEW-1')->firstOrFail();

        $this->assertSame($context['company']->id, $register->company_id);
        $this->assertSame($context['branch']->id, $register->branch_id);
        $this->assertSame(10, (int) $register->total_packages);
        $this->assertSame(400.0, (float) $register->total_delivery_freight);
        $this->assertSame(250.5, (float) $register->total_crossing_freight);

        $this->assertDatabaseCount('inward_register_details', 2);
        $this->assertDatabaseHas('inward_register_details', [
            'inward_register_id' => $register->id,
            'gr_no' => 'GR-DELIVER',
            'disposition' => 'delivery',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('inward_register_details', [
            'inward_register_id' => $register->id,
            'gr_no' => 'GR-FORWARD',
            'disposition' => 'forward',
            'status' => 'pending',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);

        $this->actingAs($context['user'])
            ->from(route('inward-registers.create'))
            ->post(route('inward-registers.store'), [])
            ->assertRedirect(route('inward-registers.create'))
            ->assertSessionHasErrors([
                'inward_register_no' => 'Inward No. is required.',
                'date' => 'Date is required.',
                'from_station_id' => 'From Station is required.',
                'to_station_id' => 'To Station is required.',
                'challan_number' => 'Challan No. is required.',
                'challan_date' => 'Challan Date is required.',
                'driver_name' => 'Driver Name is required.',
                'truck_id' => 'Truck No. is required.',
                'details',
            ]);

        $this->assertDatabaseCount('inward_registers', 0);
    }

    public function test_store_validates_detail_rows(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);

        $this->actingAs($context['user'])
            ->post(route('inward-registers.store'), $this->payload($context, [
                'details' => [[]],
            ]))
            ->assertSessionHasErrors([
                'details.0.gr_no',
                'details.0.date',
                'details.0.from_station_id',
                'details.0.to_station_id',
                'details.0.consignor_id',
                'details.0.consignee_id',
                'details.0.good_id',
                'details.0.no_of_packages',
                'details.0.weight',
                'details.0.basis_of_booking',
                'details.0.freight',
                'details.0.agent_id',
            ]);

        $this->assertDatabaseCount('inward_registers', 0);
    }

    public function test_store_rejects_negative_numbers_and_unknown_references(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);

        $this->actingAs($context['user'])
            ->post(route('inward-registers.store'), $this->payload($context, [
                'truck_id' => 999999,
                'details' => [
                    $this->detailPayload($context, [
                        'freight' => -1,
                        'weight' => -5,
                        'no_of_packages' => -2,
                        'agent_id' => 999999,
                    ]),
                ],
            ]))
            ->assertSessionHasErrors([
                'truck_id',
                'details.0.freight',
                'details.0.weight',
                'details.0.no_of_packages',
                'details.0.agent_id',
            ]);
    }

    public function test_store_aborts_when_branch_has_no_station(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);
        $context['branch']->forceFill(['station_id' => null])->save();

        $this->actingAs($context['user'])
            ->post(route('inward-registers.store'), $this->payload($context))
            ->assertStatus(422);

        $this->assertDatabaseCount('inward_registers', 0);
    }

    public function test_show_renders_register_with_relations(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);
        $register = $this->makeRegister($context, 'IR-SHOW-1');

        $this->actingAs($context['user'])
            ->get(route('inward-registers.show', $register))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('InwardRegisters/Show')
                ->where('register.id', $register->id)
                ->where('register.truck.truck_number', 'MH-01-0001')
                ->has('register.details', 1)
                ->has('accounting')
            );
    }

    public function test_edit_page_renders_register_and_lookups(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);
        $register = $this->makeRegister($context, 'IR-EDIT-1');

        $this->actingAs($context['user'])
            ->get(route('inward-registers.edit', $register))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('InwardRegisters/Edit')
                ->where('register.id', $register->id)
                ->has('register.details', 1)
                ->has('stations', 2)
                ->has('trucks', 1)
                ->has('goods', 1)
                ->has('parties', 2)
                ->has('agents', 1)
            );
    }

    public function test_update_changes_register_updates_existing_adds_new_and_removes_omitted_details(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);
        $register = $this->makeRegister($context, 'IR-UPD-1');
        $kept = $register->details()->firstOrFail();
        $removed = $this->makeDetail($context, $register, ['gr_no' => 'GR-REMOVED']);

        $this->actingAs($context['user'])
            ->put(route('inward-registers.update', $register), $this->payload($context, [
                'inward_register_no' => 'IR-UPD-1',
                'driver_name' => 'New Driver',
                'details' => [
                    $this->detailPayload($context, [
                        'id' => $kept->id,
                        'gr_no' => 'GR-KEPT-EDITED',
                        'to_station_id' => $context['fromStation']->id,
                        'freight' => 700,
                        'no_of_packages' => 7,
                    ]),
                    $this->detailPayload($context, [
                        'gr_no' => 'GR-ADDED',
                        'to_station_id' => $context['toStation']->id,
                        'freight' => 300,
                        'no_of_packages' => 3,
                    ]),
                ],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('inward-registers.index'))
            ->assertSessionHas('success', 'Inward Register updated successfully.');

        $register->refresh();
        $this->assertSame('New Driver', $register->driver_name);
        $this->assertSame(10, (int) $register->total_packages);
        $this->assertSame(700.0, (float) $register->total_delivery_freight);
        $this->assertSame(300.0, (float) $register->total_crossing_freight);

        $this->assertDatabaseHas('inward_register_details', [
            'id' => $kept->id,
            'gr_no' => 'GR-KEPT-EDITED',
            'disposition' => 'delivery',
        ]);
        $this->assertDatabaseHas('inward_register_details', [
            'inward_register_id' => $register->id,
            'gr_no' => 'GR-ADDED',
            'disposition' => 'forward',
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('inward_register_details', ['id' => $removed->id, 'deleted_at' => null]);
        $this->assertSame(2, $register->details()->count());
    }

    public function test_update_keeps_destination_of_challaned_detail(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);
        $register = $this->makeRegister($context, 'IR-UPD-2');
        $detail = $register->details()->firstOrFail();
        $detail->update([
            'status' => 'challaned',
            'to_station_id' => $context['toStation']->id,
            'disposition' => 'forward',
        ]);

        $this->actingAs($context['user'])
            ->put(route('inward-registers.update', $register), $this->payload($context, [
                'details' => [
                    $this->detailPayload($context, [
                        'id' => $detail->id,
                        'gr_no' => 'GR-LOCKED',
                        'to_station_id' => $context['fromStation']->id,
                    ]),
                ],
            ]))
            ->assertSessionHasNoErrors();

        $detail->refresh();
        $this->assertSame('GR-LOCKED', $detail->gr_no);
        $this->assertSame($context['toStation']->id, (int) $detail->to_station_id);
        $this->assertSame('forward', $detail->disposition);
        $this->assertSame('challaned', $detail->status);
    }

    public function test_update_treats_unknown_detail_id_as_validation_error(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);
        $register = $this->makeRegister($context, 'IR-UPD-3');
        $otherRegister = $this->makeRegister($context, 'IR-UPD-4');
        $foreignDetail = $otherRegister->details()->firstOrFail();

        $this->actingAs($context['user'])
            ->put(route('inward-registers.update', $register), $this->payload($context, [
                'details' => [
                    $this->detailPayload($context, ['id' => $foreignDetail->id]),
                ],
            ]))
            ->assertSessionHasErrors('details.0.id');
    }

    public function test_update_validates_required_fields(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);
        $register = $this->makeRegister($context, 'IR-UPD-5');

        $this->actingAs($context['user'])
            ->from(route('inward-registers.edit', $register))
            ->put(route('inward-registers.update', $register), [])
            ->assertRedirect(route('inward-registers.edit', $register))
            ->assertSessionHasErrors(['inward_register_no', 'date', 'truck_id', 'details']);
    }

    public function test_update_aborts_when_branch_has_no_station(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);
        $register = $this->makeRegister($context, 'IR-UPD-6');
        $context['branch']->forceFill(['station_id' => null])->save();

        $this->actingAs($context['user'])
            ->put(route('inward-registers.update', $register), $this->payload($context))
            ->assertStatus(422);
    }

    public function test_destroy_soft_deletes_register_with_audit_fields(): void
    {
        $context = $this->registerContext(self::ALL_PERMISSIONS);
        $register = $this->makeRegister($context, 'IR-DEL-1');

        $this->actingAs($context['user'])
            ->delete(route('inward-registers.destroy', $register), ['delete_remark' => 'Duplicate entry'])
            ->assertRedirect(route('inward-registers.index'))
            ->assertSessionHas('success', 'Inward Register deleted successfully.');

        $this->assertSoftDeleted('inward_registers', ['id' => $register->id]);
        $this->assertDatabaseHas('inward_registers', [
            'id' => $register->id,
            'delete_remark' => 'Duplicate entry',
            'deleted_by' => $context['user']->id,
        ]);

        $this->actingAs($context['user'])
            ->get(route('inward-registers.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('registers.data', 0));
    }

    public function test_routes_require_matching_permissions(): void
    {
        $context = $this->registerContext(['view inward_registers']);
        $register = $this->makeRegister($context, 'IR-PERM-1');
        $user = $context['user'];

        $this->actingAs($user)->get(route('inward-registers.index'))->assertOk();
        $this->actingAs($user)->get(route('inward-registers.show', $register))->assertOk();
        $this->actingAs($user)->get(route('inward-registers.create'))->assertForbidden();
        $this->actingAs($user)->post(route('inward-registers.store'), [])->assertForbidden();
        $this->actingAs($user)->get(route('inward-registers.edit', $register))->assertForbidden();
        $this->actingAs($user)->put(route('inward-registers.update', $register), [])->assertForbidden();
        $this->actingAs($user)->delete(route('inward-registers.destroy', $register))->assertForbidden();
    }

    public function test_user_without_permissions_cannot_list_registers(): void
    {
        $context = $this->registerContext([]);

        $this->actingAs($context['user'])
            ->get(route('inward-registers.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('inward-registers.index'))->assertRedirect(route('login'));
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    private function registerContext(array $permissions, string $suffix = 'A'): array
    {
        $context = $this->accountingContext($permissions, $suffix);

        $context['truck'] = Truck::create([
            'company_id' => $context['company']->id,
            'truck_number' => $suffix === 'A' ? 'MH-01-0001' : "MH-01-000{$suffix}",
        ]);
        $context['consignor'] = $this->makeParty($context, "Consignor {$suffix}");
        $context['consignee'] = $this->makeParty($context, "Consignee {$suffix}");
        $context['good'] = Good::create(['owner_id' => $context['owner']->id, 'name' => "Goods {$suffix}"]);
        $context['agent'] = Agent::create(['company_id' => $context['company']->id, 'name' => "Agent {$suffix}"]);

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeParty(array $context, string $name): Party
    {
        return Party::create([
            'company_id' => $context['company']->id,
            'name' => $name,
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $attributes
     */
    private function makeRegister(array $context, string $number, array $attributes = []): InwardRegister
    {
        $register = InwardRegister::create(array_merge([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'inward_register_no' => $number,
            'date' => now()->toDateString(),
            'challan_number' => 'CH-'.$number,
            'challan_date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'truck_id' => $context['truck']->id,
            'driver_name' => 'Test Driver',
            'total_packages' => 5,
            'total_delivery_freight' => 500,
            'total_crossing_freight' => 0,
        ], $attributes));

        $this->makeDetail($context, $register);

        return $register;
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $attributes
     */
    private function makeDetail(array $context, InwardRegister $register, array $attributes = []): InwardRegisterDetail
    {
        return InwardRegisterDetail::create(array_merge([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'inward_register_id' => $register->id,
            'gr_no' => 'GR-'.$register->inward_register_no,
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'consignor_id' => $context['consignor']->id,
            'consignee_id' => $context['consignee']->id,
            'good_id' => $context['good']->id,
            'no_of_packages' => 5,
            'weight' => 50,
            'freight' => 500,
            'agent_id' => $context['agent']->id,
            'disposition' => 'delivery',
            'status' => 'pending',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $context, array $overrides = []): array
    {
        return array_merge([
            'inward_register_no' => 'IR-NEW-1',
            'date' => now()->toDateString(),
            'from_station_id' => $context['toStation']->id,
            'to_station_id' => $context['fromStation']->id,
            'challan_number' => 'CH-100',
            'challan_date' => now()->toDateString(),
            'driver_name' => 'Ramesh',
            'truck_id' => $context['truck']->id,
            'details' => [$this->detailPayload($context)],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function detailPayload(array $context, array $overrides = []): array
    {
        return array_merge([
            'gr_no' => 'GR-100',
            'date' => now()->toDateString(),
            'from_station_id' => $context['toStation']->id,
            'to_station_id' => $context['fromStation']->id,
            'consignor_id' => $context['consignor']->id,
            'consignee_id' => $context['consignee']->id,
            'good_id' => $context['good']->id,
            'no_of_packages' => 5,
            'weight' => 50,
            'basis_of_booking' => 'Paid',
            'freight' => 500,
            'home_delivery_charge' => 0,
            'private_marka' => 'PM',
            'agent_id' => $context['agent']->id,
        ], $overrides);
    }
}
