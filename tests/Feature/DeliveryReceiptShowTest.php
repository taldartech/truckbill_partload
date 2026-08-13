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
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DeliveryReceiptShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_delivery_receipt_show(): void
    {
        $context = $this->createShowContext(['view delivery_receipts']);

        $response = $this->actingAs($context['user'])
            ->get(route('delivery-receipts.show', $context['receipt']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('DeliveryReceipts/Show')
            ->where('receipt.id', $context['receipt']->id)
            ->where('receipt.delivery_receipt_no', 'SHOW-DR-100')
            ->where('receipt.consignee.name', 'Show Consignee')
            ->where('receipt.payment_due_from.name', 'Show Payer')
            ->where('gr_source.consignor', 'Show Consignor')
            ->where('gr_source.goods_name', 'Show Goods')
            ->where('gr_source.from_station', 'From Show')
            ->where('gr_source.to_station', 'Show Station')
            ->where('gr_source.packages', 5)
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_delivery_receipt_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('delivery-receipts.show', $context['receipt']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, receipt: DeliveryReceipt}
     */
    private function createShowContext(array $permissions): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Delivery Receipt Show Co',
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
        $station = Station::create(['name' => 'Show Station', 'owner_id' => $owner->id]);
        $fromStation = Station::create(['name' => 'From Show', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Delivery Receipt Show Branch',
            'address' => '1 Delivery Show Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'station_id' => $station->id,
            'contact_person' => 'Show Admin',
            'contact_number' => '9876543210',
            'email' => 'delivery-receipt-show@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $consignor = Party::create([
            'company_id' => $company->id,
            'name' => 'Show Consignor',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile' => '9000000001',
        ]);

        $consignee = Party::create([
            'company_id' => $company->id,
            'name' => 'Show Consignee',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile' => '9000000002',
            'gst_number' => '27AAAAA0000A1Z5',
            'address' => 'Consignee Street',
        ]);

        $payer = Party::create([
            'company_id' => $company->id,
            'name' => 'Show Payer',
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);

        $good = Good::create([
            'owner_id' => $owner->id,
            'name' => 'Show Goods',
        ]);

        $register = InwardRegister::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'date' => now()->subDay()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $station->id,
        ]);

        InwardRegisterDetail::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inward_register_id' => $register->id,
            'gr_no' => 'GR-500',
            'date' => now()->subDays(2)->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $station->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'good_id' => $good->id,
            'no_of_packages' => 5,
            'weight' => 120,
            'freight' => 1000,
            'home_delivery_charge' => 100,
            'private_marka' => 'MARK-A',
            'disposition' => 'delivery',
            'status' => 'pending',
            'basis_of_booking' => 'To Pay',
        ]);

        $receipt = DeliveryReceipt::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'delivery_receipt_no' => 'SHOW-DR-100',
            'date' => now()->toDateString(),
            'gr_no_bilty_no' => 'GR-500',
            'mode_of_payment' => 'Due',
            'consignee_id' => $consignee->id,
            'haste' => 'Handle with care',
            'payment_due_from_id' => $payer->id,
            'freight' => 1000,
            'handling_charge' => 50,
            'delivery_charge' => 100,
            'stationery_charge' => 20,
            'demurrage' => 0,
            'other_charge' => 10,
            'carriage_charge' => 30,
            'total_amount' => 1210,
            'gst_on' => 'total_charge',
            'gst_rate' => 5,
            'gst' => 60.5,
            'gst_pay_by' => 'Consignee',
            'less' => 10,
            'final_amount' => 1260.5,
        ]);

        return [
            'user' => $user,
            'receipt' => $receipt,
        ];
    }
}
