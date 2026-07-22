<?php

namespace Tests\Feature;

use App\Models\Bilty;
use App\Models\BiltyDetail;
use App\Models\Branch;
use App\Models\Challan;
use App\Models\ChallanDetail;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\Good;
use App\Models\MainChallan;
use App\Models\MainChallanDetail;
use App\Models\PackageType;
use App\Models\Party;
use App\Models\State;
use App\Models\Station;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MainChallanPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_challan_print_uses_challan_style_layout(): void
    {
        $context = $this->createPrintContext();
        $user = $context['user'];
        $mainChallan = $context['main_challan'];
        $challan = $context['challan'];

        $response = $this->actingAs($user)->get(route('main-challans.print', $mainChallan));

        $response->assertOk();
        $response->assertSee('MAIN CHALLAN', false);
        $response->assertSee('Main Challan No:', false);
        $response->assertSee('Challan No.', false);
        $response->assertSee('To Station', false);
        $response->assertSee('Paid Frt', false);
        $response->assertSee('To Pay Frt', false);
        $response->assertSee('Lab/Cro', false);
        $response->assertSee('Kat/HD', false);
        $response->assertSee('Balance Freight', false);
        $response->assertSee('Total Booking Freight', false);
        $response->assertSee('Signature of Driver or Truck Owner', false);
        $response->assertSee($challan->challan_number, false);
        $response->assertDontSee('GR/Bilty No.', false);
        $response->assertDontSee('Consignee Name', false);
    }

    /**
     * @return array{user: User, main_challan: MainChallan, challan: Challan}
     */
    private function createPrintContext(): array
    {
        Permission::findOrCreate('view challans');

        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Print Co', 'user_id' => $owner->id]);

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
        $fromStation = Station::create(['name' => 'Mumbai', 'owner_id' => $owner->id]);
        $toStation = Station::create(['name' => 'Pune', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Print Branch',
            'address' => '12 Print Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'station_id' => $fromStation->id,
            'contact_person' => 'Printer',
            'contact_number' => '9999999999',
            'email' => 'print@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo('view challans');

        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'MH12AB1234',
        ]);

        $consignor = Party::create([
            'company_id' => $company->id,
            'name' => 'Consignor One',
        ]);

        $consignee = Party::create([
            'company_id' => $company->id,
            'name' => 'Consignee One',
        ]);

        $good = Good::create([
            'owner_id' => $owner->id,
            'name' => 'Cotton Bales',
        ]);

        $packageType = PackageType::create([
            'owner_id' => $owner->id,
            'name' => 'Bags',
        ]);

        $bilty = Bilty::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'bilty_number' => 'BLT/0001',
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'private_marka' => 'MARK-1',
            'freight' => 1500,
        ]);

        BiltyDetail::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'bilty_id' => $bilty->id,
            'good_id' => $good->id,
            'package_type_id' => $packageType->id,
            'no_of_packages' => 10,
            'gross_weight' => 250,
        ]);

        $challan = Challan::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'challan_number' => 'CH/0001',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'truck_id' => $truck->id,
            'driver_name' => 'Ramesh',
            'status' => 'Created',
        ]);

        ChallanDetail::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'challan_id' => $challan->id,
            'bilty_id' => $bilty->id,
        ]);

        $mainChallan = MainChallan::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'main_challan_number' => 'MCH/0001',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'truck_id' => $truck->id,
            'driver_name' => 'Ramesh',
            'crossing_freight' => 0,
            'gst' => 0,
            'labour_charge' => 100,
            'kat' => 0,
            'home_delivery_charge' => 0,
            'truck_freight' => 5000,
            'cash_received' => 0,
            'cash_paid' => 0,
            'total_amount' => 5000,
            'pay_by' => 'Driver',
        ]);

        MainChallanDetail::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'main_challan_id' => $mainChallan->id,
            'challan_id' => $challan->id,
        ]);

        return [
            'user' => $user,
            'main_challan' => $mainChallan,
            'challan' => $challan,
        ];
    }
}
