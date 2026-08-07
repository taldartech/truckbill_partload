<?php

namespace Tests\Feature;

use App\Models\Bilty;
use App\Models\BiltyDetail;
use App\Models\BiltyEwayBill;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\Good;
use App\Models\PackageType;
use App\Models\Party;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BiltyPrintViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_bilty_print_matches_tax_invoice_layout_and_shows_logo(): void
    {
        $context = $this->createPrintContext([
            'company_logo' => '/storage/company_logos/demo.png',
            'use_company_logo' => true,
            'payment_type' => 'To Pay',
            'show_amount' => true,
        ]);

        $response = $this->actingAs($context['user'])->get(route('bilties.print', $context['bilty']));

        $response->assertOk();
        $response->assertSee('Bilty / Tax Invoice', false);
        $response->assertSee('Consignor:', false);
        $response->assertSee('Consignee:', false);
        $response->assertSee('From Station:', false);
        $response->assertSee('To Station:', false);
        $response->assertSee('Delivery At:', false);
        $response->assertSee('Item Description', false);
        $response->assertSee('Packages', false);
        $response->assertSee('Weight', false);
        $response->assertSee('Charges', false);
        $response->assertSee('Amount', false);
        $response->assertSee('Handling Charge', false);
        $response->assertSee('Bilty Charge', false);
        $response->assertSee('Total Freight', false);
        $response->assertDontSee('Risk/Door Delivery', false);
        $response->assertDontSee('Less Advance Received', false);
        $response->assertSee('Net Amount', false);
        $response->assertSee('E-Way Bill:', false);
        $response->assertSee('Private Marka:', false);
        $response->assertSee($context['bilty']->bilty_number, false);
        $response->assertSee($context['company']->name, false);
        $response->assertSee(url('/storage/company_logos/demo.png'), false);
        $response->assertSee('1,500.00', false);
        $response->assertDontSee('Basis of Booking', false);
        $response->assertDontSee('Method of Packing', false);
    }

    public function test_hidden_amount_copies_show_tbb_instead_of_payment_method(): void
    {
        $context = $this->createPrintContext([
            'payment_type' => 'To Pay',
            'show_amount' => false,
        ]);

        $response = $this->actingAs($context['user'])->get(route('bilties.print', $context['bilty']));

        $response->assertOk();
        $response->assertSee('TBB', false);
        $response->assertSee('Driver Copy', false);
        $response->assertDontSee('TO PAY', false);
        $response->assertDontSee('1,500.00', false);
    }

    public function test_tbb_print_with_amounts_hidden_shows_tbb_label(): void
    {
        $context = $this->createPrintContext([
            'payment_type' => 'TBB',
            'show_amount' => false,
        ]);

        $response = $this->actingAs($context['user'])->get(route('bilties.print', $context['bilty']));

        $response->assertOk();
        $response->assertSee('TBB', false);
        $response->assertDontSee('To BE BILLED', false);
        $response->assertDontSee('1,500.00', false);
    }

    public function test_shown_amount_copies_keep_actual_payment_stamp(): void
    {
        $context = $this->createPrintContext([
            'payment_type' => 'To Pay',
            'show_amount' => true,
        ]);

        $response = $this->actingAs($context['user'])->get(route('bilties.print', $context['bilty']));

        $response->assertOk();
        $response->assertSee('TO PAY', false);
        $response->assertSee('1,500.00', false);
    }

    public function test_print_lists_multiple_eway_bills_with_expiry_dates(): void
    {
        $context = $this->createPrintContext([
            'payment_type' => 'To Pay',
            'show_amount' => true,
        ]);

        BiltyEwayBill::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $context['bilty']->id,
            'eway_bill_no' => '111122223333',
            'eway_bill_valid_till' => '2026-07-01',
        ]);

        BiltyEwayBill::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $context['bilty']->id,
            'eway_bill_no' => '444455556666',
            'eway_bill_valid_till' => '2026-07-05',
        ]);

        $response = $this->actingAs($context['user'])->get(route('bilties.print', $context['bilty']));

        $response->assertOk();
        $response->assertSee('111122223333 (01-Jul-2026), 444455556666 (05-Jul-2026)', false);
    }

    /**
     * @param  array{company_logo?: string|null, use_company_logo?: bool, payment_type?: string, show_amount?: bool}  $options
     * @return array{user: User, bilty: Bilty, company: Company, branch: Branch}
     */
    private function createPrintContext(array $options = []): array
    {
        Permission::findOrCreate('view bilties');

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Bittu Road Carriers',
            'user_id' => $owner->id,
            'company_logo' => $options['company_logo'] ?? null,
            'gst_number' => '23AGIPD3853A1Z1',
            'has_gst' => true,
            'pan_no' => 'AGIPD3853A',
            'contact_number' => '9827287881',
            'email' => 'brctransport3@gmail.com',
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
        $fromState = State::create(['country_id' => $country->id, 'name' => 'Madhya Pradesh', 'code' => 'MP']);
        $toState = State::create(['country_id' => $country->id, 'name' => 'Rajasthan', 'code' => 'RJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $fromState->id, 'name' => 'Indore']);
        $fromStation = Station::create(['name' => 'Indore', 'owner_id' => $owner->id, 'state_id' => $fromState->id]);
        $toStation = Station::create(['name' => 'Udaipur', 'owner_id' => $owner->id, 'state_id' => $toState->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'L01-Loha Mandi',
            'address' => '31 A, T.S. Navlakha, Loha Mandi Indore 452001',
            'country_id' => $country->id,
            'state_id' => $fromState->id,
            'city_id' => $city->id,
            'pin_code' => '452001',
            'station_id' => $fromStation->id,
            'contact_person' => 'Admin',
            'contact_number' => '07314980620',
            'email' => 'brctransport3@gmail.com',
            'use_company_logo' => $options['use_company_logo'] ?? true,
            'has_gst' => true,
            'gst_number' => '23AGIPD3853A1Z1',
        ]);

        $showAmount = (bool) ($options['show_amount'] ?? true);
        $branch->settings()->update([
            'bilty_print_config' => [
                'note' => '',
                'copies' => [
                    'consignor' => ['enabled' => false, 'show_amount_by_bob' => BranchSetting::defaultShowAmountByBob(false)],
                    'consignee' => ['enabled' => false, 'show_amount_by_bob' => BranchSetting::defaultShowAmountByBob(false)],
                    'driver' => [
                        'enabled' => true,
                        'show_amount_by_bob' => BranchSetting::defaultShowAmountByBob($showAmount),
                    ],
                    'office' => ['enabled' => false, 'show_amount_by_bob' => BranchSetting::defaultShowAmountByBob(false)],
                ],
            ],
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo('view bilties');

        $consignor = Party::create([
            'company_id' => $company->id,
            'name' => 'Sudarshan Auto Industries Pvt. Ltd.',
            'gst_number' => '23AAQCS6764E1Z1',
            'mobile' => '9000000001',
        ]);

        $consignee = Party::create([
            'company_id' => $company->id,
            'name' => 'Khalsa Sales Corporation',
            'gst_number' => '08AOQPS4112P1Z8',
            'mobile' => '9000000002',
        ]);

        $good = Good::create([
            'owner_id' => $owner->id,
            'name' => 'Motorcycle Tyre',
        ]);

        $packageType = PackageType::create([
            'owner_id' => $owner->id,
            'name' => 'Loose',
        ]);

        $bilty = Bilty::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'bilty_number' => '4597',
            'date' => '2026-06-29',
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'payment_type' => $options['payment_type'] ?? 'To Pay',
            'delivery_at' => 'Door Delivery',
            'private_marka' => 'PM-1',
            'invoice_number' => '245',
            'declared_value' => 154821,
            'freight' => 1500,
            'handling_charge' => 50,
            'cartage' => 0,
            'bilty_charge' => 20,
            'gst' => 0,
            'gst_liability_by' => 'Consignor',
            'amount' => 1570,
            'created_by' => $user->id,
        ]);

        BiltyDetail::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'bilty_id' => $bilty->id,
            'good_id' => $good->id,
            'package_type_id' => $packageType->id,
            'no_of_packages' => 30,
            'gross_weight' => 750,
            'charged_weight' => 750,
            'rate' => 2,
            'rate_type' => 'Fixed',
        ]);

        return [
            'user' => $user,
            'bilty' => $bilty,
            'company' => $company,
            'branch' => $branch,
        ];
    }
}
