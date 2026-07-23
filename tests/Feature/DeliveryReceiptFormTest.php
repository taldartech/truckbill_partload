<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\DeliveryReceipt;
use App\Models\Good;
use App\Models\InwardRegister;
use App\Models\InwardRegisterDetail;
use App\Models\Party;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DeliveryReceiptFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_gr_lookup_returns_consignee_and_charge_defaults(): void
    {
        $context = $this->createContext();
        $detail = $context['inward_detail'];

        $response = $this->actingAs($context['user'])->getJson(
            route('delivery-receipts.gr-lookup', ['key' => 'inward:'.$detail->id])
        );

        $response->assertOk()
            ->assertJsonPath('gr_no_bilty_no', $detail->gr_no)
            ->assertJsonPath('consignee_id', $detail->consignee_id)
            ->assertJsonPath('consignee_name', $context['consignee']->name)
            ->assertJsonMissingPath('mode_of_payment')
            ->assertJsonMissingPath('payment_due_from_id')
            ->assertJsonMissingPath('risk_dd_charge')
            ->assertJsonPath('stationery_charge', 0)
            ->assertJsonPath('carriage_charge', 0)
            ->assertJsonPath('gst_on', 'total_charge')
            ->assertJsonPath('gst_pay_by', 'Consignee');
    }

    public function test_store_requires_receipt_no_and_mode_of_payment(): void
    {
        $context = $this->createContext();
        $detail = $context['inward_detail'];

        $response = $this->actingAs($context['user'])->from(route('delivery-receipts.create'))->post(route('delivery-receipts.store'), [
            'date' => now()->toDateString(),
            'gr_no_bilty_no' => $detail->gr_no,
            'consignee_id' => $context['consignee']->id,
            'final_amount' => 100,
        ]);

        $response->assertRedirect(route('delivery-receipts.create'));
        $response->assertSessionHasErrors(['delivery_receipt_no', 'mode_of_payment']);
    }

    public function test_store_requires_payment_due_from_when_mode_is_due(): void
    {
        $context = $this->createContext();
        $detail = $context['inward_detail'];

        $response = $this->actingAs($context['user'])->from(route('delivery-receipts.create'))->post(route('delivery-receipts.store'), [
            'delivery_receipt_no' => 'DR/1002',
            'date' => now()->toDateString(),
            'gr_no_bilty_no' => $detail->gr_no,
            'mode_of_payment' => 'Due',
            'consignee_id' => $context['consignee']->id,
            'final_amount' => 100,
        ]);

        $response->assertRedirect(route('delivery-receipts.create'));
        $response->assertSessionHasErrors(['payment_due_from_id']);
    }

    public function test_store_persists_renamed_columns_and_gst_fields(): void
    {
        $context = $this->createContext();
        $detail = $context['inward_detail'];

        $payload = [
            'delivery_receipt_no' => 'DR/1001',
            'date' => now()->toDateString(),
            'gr_no_bilty_no' => $detail->gr_no,
            'mode_of_payment' => 'Cash',
            'consignee_id' => $context['consignee']->id,
            'payment_due_from_id' => $context['consignee']->id,
            'freight' => 1000,
            'handling_charge' => 50,
            'delivery_charge' => 100,
            'stationery_charge' => 20,
            'demurrage' => 0,
            'other_charge' => 0,
            'carriage_charge' => 30,
            'total_amount' => 1200,
            'gst_on' => 'total_charge',
            'gst_rate' => 99,
            'gst' => 144,
            'gst_pay_by' => 'Consignee',
            'less' => 0,
            'final_amount' => 1344,
        ];

        $response = $this->actingAs($context['user'])->post(route('delivery-receipts.store'), $payload);

        $response->assertRedirect(route('delivery-receipts.index'));

        $this->assertDatabaseHas('delivery_receipts', [
            'delivery_receipt_no' => 'DR/1001',
            'mode_of_payment' => 'Cash',
            'stationery_charge' => 20,
            'carriage_charge' => 30,
            'gst_on' => 'total_charge',
            'gst_rate' => 12,
            'gst_pay_by' => 'Consignee',
            'payment_due_from_id' => null,
        ]);

        $this->assertDatabaseMissing('delivery_receipts', [
            'delivery_receipt_no' => 'DR/1001',
            'risk_dd_charge' => 0,
        ]);

        $receipt = DeliveryReceipt::query()->where('delivery_receipt_no', 'DR/1001')->first();
        $this->assertNotNull($receipt);
        $this->assertFalse(array_key_exists('risk_dd_charge', $receipt->getAttributes()));
        $this->assertFalse(array_key_exists('stationary_charge', $receipt->getAttributes()));
        $this->assertFalse(array_key_exists('cartage_charge', $receipt->getAttributes()));
        $this->assertFalse(array_key_exists('payment_due_from', $receipt->getAttributes()));
        $this->assertFalse(array_key_exists('office_name', $receipt->getAttributes()));
    }

    public function test_create_page_lists_gr_with_consignee_in_label(): void
    {
        $context = $this->createContext();

        $response = $this->actingAs($context['user'])->get(route('delivery-receipts.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('DeliveryReceipts/Create')
            ->has('gr_sources', 1)
            ->where('gr_sources.0.label', $context['inward_detail']->gr_no.' ('.$context['consignee']->name.')')
            ->where('gst_rate', 12)
        );
    }

    /**
     * @return array{user: User, consignee: Party, inward_detail: InwardRegisterDetail}
     */
    private function createContext(): array
    {
        Permission::findOrCreate('view delivery_receipts');
        Permission::findOrCreate('create delivery_receipts');
        Permission::findOrCreate('edit delivery_receipts');

        $owner = User::factory()->create();
        $company = Company::create(['name' => 'DR Co', 'user_id' => $owner->id]);

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
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Pune']);
        $fromStation = Station::create(['name' => 'Mumbai', 'owner_id' => $owner->id]);
        $station = Station::create(['name' => 'Pune', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'DR Branch',
            'address' => '1 Delivery Road',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '411001',
            'station_id' => $station->id,
            'contact_person' => 'Ops',
            'contact_number' => '9000000000',
            'email' => 'dr@example.com',
            'has_gst' => true,
            'gst_slab' => 12,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo(['view delivery_receipts', 'create delivery_receipts', 'edit delivery_receipts']);

        $consignee = Party::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Delivery Consignee',
        ]);

        $good = Good::create([
            'owner_id' => $owner->id,
            'name' => 'Boxes',
        ]);

        $register = InwardRegister::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $station->id,
        ]);

        $detail = InwardRegisterDetail::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inward_register_id' => $register->id,
            'gr_no' => 'GR/9001',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $station->id,
            'consignee_id' => $consignee->id,
            'good_id' => $good->id,
            'no_of_packages' => 5,
            'weight' => 120,
            'freight' => 1000,
            'home_delivery_charge' => 100,
            'disposition' => 'delivery',
            'status' => 'pending',
            'basis_of_booking' => 'To Pay',
        ]);

        return [
            'user' => $user,
            'consignee' => $consignee,
            'inward_detail' => $detail,
        ];
    }
}
