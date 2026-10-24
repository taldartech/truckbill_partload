<?php

namespace Tests\Feature\E2E;

use App\Models\Agent;
use App\Models\Bilty;
use App\Models\Challan;
use App\Models\DeliveryReceipt;
use App\Models\Good;
use App\Models\Invoice;
use App\Models\InwardRegister;
use App\Models\InwardRegisterDetail;
use App\Models\PackageType;
use App\Models\Party;
use App\Models\Station;
use App\Models\Truck;
use App\Models\TruckType;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Support\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

/**
 * PHP portal end-to-end operational journeys:
 * masters → bilty → challan → reports;
 * TBB bilty → invoice;
 * inward → delivery receipt.
 */
class OperationalJourneyTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_full_ops_journey_from_masters_through_challan_and_reports(): void
    {
        $context = $this->createTenantContext(RolePermissions::adminPermissions());
        app(EnsureCompanyAccounting::class)->handle($context['company']);
        $user = $context['user'];

        $destination = Station::create([
            'name' => 'Destination Hub',
            'owner_id' => $context['owner']->id,
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'country_id' => $context['country']->id,
        ]);

        $this->actingAs($user)
            ->from(route('goods.index'))
            ->post(route('goods.store'), ['name' => 'Journey Goods'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('goods.index'));

        $this->actingAs($user)
            ->from(route('package-types.index'))
            ->post(route('package-types.store'), ['name' => 'Journey Box'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('package-types.index'));

        $this->actingAs($user)
            ->from(route('truck-types.index'))
            ->post(route('truck-types.store'), ['name' => 'Journey Open'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('truck-types.index'));

        $this->actingAs($user)
            ->from(route('parties.index'))
            ->post(route('parties.store'), [
                'name' => 'Journey Consignor',
                'address' => '1 Consignor Road',
                'city_id' => $context['city']->id,
                'state_id' => $context['state']->id,
                'mobile' => '9811111111',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->from(route('parties.index'))
            ->post(route('parties.store'), [
                'name' => 'Journey Consignee',
                'address' => '2 Consignee Road',
                'city_id' => $context['city']->id,
                'state_id' => $context['state']->id,
                'mobile' => '9822222222',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->from(route('agents.index'))
            ->post(route('agents.store'), [
                'name' => 'Journey Agent',
                'address' => '3 Agent Road',
                'state_id' => $context['state']->id,
                'city_id' => $context['city']->id,
                'mobile' => '9833333333',
            ])
            ->assertSessionHasNoErrors();

        $good = Good::query()->where('name', 'Journey Goods')->firstOrFail();
        $packageType = PackageType::query()->where('name', 'Journey Box')->firstOrFail();
        $truckType = TruckType::query()->where('name', 'Journey Open')->firstOrFail();
        $consignor = Party::query()->where('name', 'Journey Consignor')->firstOrFail();
        $consignee = Party::query()->where('name', 'Journey Consignee')->firstOrFail();
        $agent = Agent::query()->where('name', 'Journey Agent')->firstOrFail();

        $this->actingAs($user)
            ->post(route('trucks.store'), [
                'truck_number' => 'RJ14E2E001',
                'truck_type_id' => $truckType->id,
                'agent_id' => $agent->id,
                'status' => 'Available',
                'current_state_id' => $context['state']->id,
                'current_city_id' => $context['city']->id,
                'current_station_id' => $context['station']->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('trucks.index'));

        $truck = Truck::query()->where('truck_number', 'RJ14E2E001')->firstOrFail();

        $this->actingAs($user)
            ->post(route('bilties.store'), [
                'bilty_number' => 'E2E-BLT-1',
                'consignor_id' => $consignor->id,
                'consignee_id' => $consignee->id,
                'from_station_id' => $context['station']->id,
                'to_station_id' => $destination->id,
                'date' => now()->toDateString(),
                'payment_type' => 'To Pay',
                'details' => [[
                    'good_id' => $good->id,
                    'package_type_id' => $packageType->id,
                    'no_of_packages' => 5,
                    'gross_weight' => 100,
                    'charged_weight' => 100,
                    'rate_type' => 'Fixed',
                    'rate' => 250,
                ]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('bilties.index'));

        $bilty = Bilty::query()->where('bilty_number', 'E2E-BLT-1')->firstOrFail();
        $this->assertSame('Pending', $bilty->status);

        $this->actingAs($user)
            ->get(route('bilties.show', $bilty))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Bilties/Show')
                ->where('bilty.id', $bilty->id)
            );

        $this->actingAs($user)
            ->post(route('challans.store'), [
                'from_station_id' => $context['station']->id,
                'to_station_id' => $destination->id,
                'driver_name' => 'E2E Driver',
                'truck_id' => $truck->id,
                'date' => now()->toDateString(),
                'status' => 'Created',
                'details' => [[
                    'row_type' => 'bilty',
                    'bilty_id' => $bilty->id,
                ]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('challans.index'));

        $challan = Challan::query()->where('truck_id', $truck->id)->latest('id')->firstOrFail();
        $this->assertSame('In Transit', $bilty->fresh()->status);

        $this->actingAs($user)
            ->get(route('challans.show', $challan))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Challans/Show')
                ->where('challan.id', $challan->id)
            );

        $this->actingAs($user)
            ->get(route('reports.bilty-register'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Reports/BiltyRegister'));

        $this->actingAs($user)
            ->get(route('reports.challan-register'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Reports/ChallanRegister'));

        $this->actingAs($user)
            ->get(route('accounting.reports.day-book'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Accounting/Reports/DayBook'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('dashboard.mode', 'operational')
            );
    }

    public function test_tbb_bilty_to_invoice_journey(): void
    {
        $context = $this->createTenantContext(RolePermissions::adminPermissions());
        app(EnsureCompanyAccounting::class)->handle($context['company']);
        $user = $context['user'];

        $destination = Station::create([
            'name' => 'Invoice Destination',
            'owner_id' => $context['owner']->id,
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'country_id' => $context['country']->id,
        ]);

        $this->actingAs($user)->from(route('goods.index'))->post(route('goods.store'), ['name' => 'Invoice Goods']);
        $this->actingAs($user)->from(route('package-types.index'))->post(route('package-types.store'), ['name' => 'Invoice Box']);
        $this->actingAs($user)->from(route('parties.index'))->post(route('parties.store'), [
            'name' => 'Invoice Party',
            'address' => '9 Invoice Road',
            'city_id' => $context['city']->id,
            'state_id' => $context['state']->id,
            'mobile' => '9844444444',
        ]);
        $this->actingAs($user)->from(route('parties.index'))->post(route('parties.store'), [
            'name' => 'Invoice Consignee',
            'address' => '10 Invoice Road',
            'city_id' => $context['city']->id,
            'state_id' => $context['state']->id,
            'mobile' => '9855555555',
        ]);

        $good = Good::query()->where('name', 'Invoice Goods')->firstOrFail();
        $packageType = PackageType::query()->where('name', 'Invoice Box')->firstOrFail();
        $party = Party::query()->where('name', 'Invoice Party')->firstOrFail();
        $consignee = Party::query()->where('name', 'Invoice Consignee')->firstOrFail();

        $this->actingAs($user)
            ->post(route('bilties.store'), [
                'bilty_number' => 'E2E-TBB-1',
                'consignor_id' => $party->id,
                'consignee_id' => $consignee->id,
                'from_station_id' => $context['station']->id,
                'to_station_id' => $destination->id,
                'date' => now()->toDateString(),
                'payment_type' => 'TBB',
                'details' => [[
                    'good_id' => $good->id,
                    'package_type_id' => $packageType->id,
                    'no_of_packages' => 2,
                    'gross_weight' => 40,
                    'charged_weight' => 40,
                    'rate_type' => 'Fixed',
                    'rate' => 500,
                ]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('bilties.index'));

        $bilty = Bilty::query()->where('bilty_number', 'E2E-TBB-1')->firstOrFail();
        $detail = $bilty->biltyDetails()->firstOrFail();

        $this->actingAs($user)
            ->post(route('invoices.store'), [
                'invoice_no' => 'E2E-INV-1',
                'date' => now()->toDateString(),
                'party_id' => $party->id,
                'remark' => 'E2E invoice',
                'bilty_ids' => [$bilty->id],
                'bilty_detail_freight' => [
                    $bilty->id => [
                        ['id' => $detail->id, 'rate_type' => 'Fixed', 'rate' => 500],
                    ],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('invoices.index'));

        $invoice = Invoice::query()->where('invoice_no', 'E2E-INV-1')->firstOrFail();
        $this->assertSame($party->id, $invoice->party_id);

        $this->actingAs($user)
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Invoices/Show')
                ->where('invoice.id', $invoice->id)
            );
    }

    public function test_inward_register_to_delivery_receipt_journey(): void
    {
        $context = $this->createTenantContext(RolePermissions::adminPermissions());
        app(EnsureCompanyAccounting::class)->handle($context['company']);
        $user = $context['user'];

        $fromStation = Station::create([
            'name' => 'Inward Origin',
            'owner_id' => $context['owner']->id,
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'country_id' => $context['country']->id,
        ]);

        $this->actingAs($user)->from(route('goods.index'))->post(route('goods.store'), ['name' => 'Delivery Goods']);
        $this->actingAs($user)->from(route('truck-types.index'))->post(route('truck-types.store'), ['name' => 'Delivery Open']);
        $this->actingAs($user)->from(route('parties.index'))->post(route('parties.store'), [
            'name' => 'Delivery Consignor',
            'address' => '11 Delivery Road',
            'city_id' => $context['city']->id,
            'state_id' => $context['state']->id,
            'mobile' => '9866666666',
        ]);
        $this->actingAs($user)->from(route('parties.index'))->post(route('parties.store'), [
            'name' => 'Delivery Consignee',
            'address' => '12 Delivery Road',
            'city_id' => $context['city']->id,
            'state_id' => $context['state']->id,
            'mobile' => '9877777777',
        ]);
        $this->actingAs($user)->from(route('agents.index'))->post(route('agents.store'), [
            'name' => 'Delivery Agent',
            'address' => '13 Delivery Road',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9888888888',
        ]);

        $good = Good::query()->where('name', 'Delivery Goods')->firstOrFail();
        $truckType = TruckType::query()->where('name', 'Delivery Open')->firstOrFail();
        $consignor = Party::query()->where('name', 'Delivery Consignor')->firstOrFail();
        $consignee = Party::query()->where('name', 'Delivery Consignee')->firstOrFail();
        $agent = Agent::query()->where('name', 'Delivery Agent')->firstOrFail();

        $this->actingAs($user)
            ->post(route('trucks.store'), [
                'truck_number' => 'RJ14DEL001',
                'truck_type_id' => $truckType->id,
                'agent_id' => $agent->id,
                'status' => 'Available',
                'current_state_id' => $context['state']->id,
                'current_city_id' => $context['city']->id,
                'current_station_id' => $context['station']->id,
            ])
            ->assertSessionHasNoErrors();

        $truck = Truck::query()->where('truck_number', 'RJ14DEL001')->firstOrFail();

        // to_station = branch station → disposition delivery (eligible for delivery receipt)
        $this->actingAs($user)
            ->post(route('inward-registers.store'), [
                'inward_register_no' => 'E2E-IR-1',
                'date' => now()->toDateString(),
                'from_station_id' => $fromStation->id,
                'to_station_id' => $context['station']->id,
                'challan_number' => 'E2E-IR-CH-1',
                'challan_date' => now()->toDateString(),
                'driver_name' => 'Delivery Driver',
                'truck_id' => $truck->id,
                'details' => [[
                    'gr_no' => 'E2E-GR-1',
                    'date' => now()->toDateString(),
                    'from_station_id' => $fromStation->id,
                    'to_station_id' => $context['station']->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'good_id' => $good->id,
                    'no_of_packages' => 3,
                    'weight' => 30,
                    'basis_of_booking' => 'To Pay',
                    'freight' => 300,
                    'home_delivery_charge' => 0,
                    'agent_id' => $agent->id,
                ]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('inward-registers.index'));

        $register = InwardRegister::query()->where('inward_register_no', 'E2E-IR-1')->firstOrFail();
        $detail = InwardRegisterDetail::query()
            ->where('inward_register_id', $register->id)
            ->where('gr_no', 'E2E-GR-1')
            ->firstOrFail();
        $this->assertSame('delivery', $detail->disposition);

        $this->actingAs($user)
            ->post(route('delivery-receipts.store'), [
                'delivery_receipt_no' => 'E2E-DR-1',
                'date' => now()->toDateString(),
                'gr_no_bilty_no' => $detail->gr_no,
                'mode_of_payment' => 'Cash',
                'consignee_id' => $consignee->id,
                'freight' => 300,
                'final_amount' => 300,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('delivery-receipts.index'));

        $receipt = DeliveryReceipt::query()->where('delivery_receipt_no', 'E2E-DR-1')->firstOrFail();
        $this->assertSame('E2E-GR-1', $receipt->gr_no_bilty_no);

        $this->actingAs($user)
            ->get(route('delivery-receipts.show', $receipt))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('DeliveryReceipts/Show')
                ->where('receipt.id', $receipt->id)
            );
    }
}
