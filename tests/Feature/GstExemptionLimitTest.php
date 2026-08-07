<?php

namespace Tests\Feature;

use App\Models\Bilty;
use App\Models\BiltyDetail;
use App\Models\Branch;
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

class GstExemptionLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_and_branch_persist_gst_exemption_limit(): void
    {
        $company = Company::create([
            'name' => 'GST Co',
            'has_gst' => true,
            'gst_number' => '23AGIPD3853A1Z1',
            'gst_type' => 'Forward',
            'gst_scheme' => 'Regular',
            'gst_slab' => 18,
            'gst_exemption_limit' => 1500,
        ]);

        $this->assertSame('1500.00', $company->fresh()->gst_exemption_limit);

        $owner = User::factory()->create();
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'MP', 'code' => 'MP']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Indore']);
        $station = Station::create(['name' => 'Indore', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'HO',
            'address' => 'Addr',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '452001',
            'station_id' => $station->id,
            'contact_person' => 'Admin',
            'contact_number' => '9876543210',
            'email' => 'ho@example.com',
            'has_gst' => true,
            'gst_number' => '23AGIPD3853A1Z1',
            'gst_type' => 'Forward',
            'gst_scheme' => 'Regular',
            'gst_slab' => 18,
            'gst_exemption_limit' => 2000,
        ]);

        $this->assertSame('2000.00', $branch->fresh()->gst_exemption_limit);
    }

    public function test_delivery_receipt_create_receives_branch_gst_exemption_limit(): void
    {
        $context = $this->createOperationalContext([
            'create delivery_receipts',
        ], [
            'gst_exemption_limit' => 1800,
        ]);

        $response = $this->actingAs($context['user'])->get(route('delivery-receipts.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('DeliveryReceipts/Create')
            ->where('gst_exemption_limit', 1800)
            ->where('gst_rate', 18)
        );
    }

    public function test_bilty_print_omits_gst_when_charge_base_does_not_exceed_limit(): void
    {
        $context = $this->createBiltyPrintContext([
            'gst_exemption_limit' => 2000,
            'gst' => 18,
            'freight' => 1500,
            'handling_charge' => 50,
            'bilty_charge' => 20,
            'amount' => 1570,
        ]);

        $response = $this->actingAs($context['user'])->get(route('bilties.print', $context['bilty']));

        $response->assertOk();
        $response->assertDontSee('GST (18%)', false);
    }

    public function test_bilty_print_includes_gst_when_charge_base_exceeds_limit(): void
    {
        // Charge base 1570 > limit 1000 → GST = 1570 * 18% = 282.60
        $context = $this->createBiltyPrintContext([
            'gst_exemption_limit' => 1000,
            'gst' => 18,
            'gst_liability_by' => 'Transporter',
            'freight' => 1500,
            'handling_charge' => 50,
            'bilty_charge' => 20,
            'amount' => 1852.60,
        ]);

        $response = $this->actingAs($context['user'])->get(route('bilties.print', $context['bilty']));

        $response->assertOk();
        $response->assertSee('GST (18%)', false);
        $response->assertSee('282.60', false);
    }

    public function test_bilty_print_still_zeroes_gst_when_liability_is_exempted(): void
    {
        $context = $this->createBiltyPrintContext([
            'gst_exemption_limit' => 100,
            'gst' => 18,
            'gst_liability_by' => 'Exempted',
            'freight' => 1500,
            'handling_charge' => 50,
            'bilty_charge' => 20,
            'amount' => 1570,
        ]);

        $response = $this->actingAs($context['user'])->get(route('bilties.print', $context['bilty']));

        $response->assertOk();
        $response->assertDontSee('GST (18%)', false);
    }

    /**
     * @param  list<string>  $permissions
     * @param  array{gst_exemption_limit?: float|int}  $branchGst
     * @return array{user: User, company: Company, branch: Branch}
     */
    private function createOperationalContext(array $permissions, array $branchGst = []): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'GST Co',
            'user_id' => $owner->id,
            'has_gst' => true,
            'gst_slab' => 18,
            'gst_exemption_limit' => $branchGst['gst_exemption_limit'] ?? 0,
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
        $state = State::create(['country_id' => $country->id, 'name' => 'MP', 'code' => 'MP']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Indore']);
        $station = Station::create(['name' => 'Indore', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'HO',
            'address' => 'Addr',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '452001',
            'station_id' => $station->id,
            'contact_person' => 'Admin',
            'contact_number' => '9876543210',
            'email' => 'ho@example.com',
            'has_gst' => true,
            'gst_number' => '23AGIPD3853A1Z1',
            'gst_type' => 'Forward',
            'gst_scheme' => 'Regular',
            'gst_slab' => 18,
            'gst_exemption_limit' => $branchGst['gst_exemption_limit'] ?? 0,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo($permissions);

        return [
            'user' => $user,
            'company' => $company,
            'branch' => $branch,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{user: User, bilty: Bilty, company: Company, branch: Branch}
     */
    private function createBiltyPrintContext(array $options = []): array
    {
        Permission::findOrCreate('view bilties');

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Print Co',
            'user_id' => $owner->id,
            'has_gst' => true,
            'gst_slab' => 18,
            'gst_exemption_limit' => $options['gst_exemption_limit'] ?? 0,
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
        $state = State::create(['country_id' => $country->id, 'name' => 'MP', 'code' => 'MP']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Indore']);
        $fromStation = Station::create(['name' => 'Indore', 'owner_id' => $owner->id]);
        $toStation = Station::create(['name' => 'Udaipur', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'HO',
            'address' => 'Addr',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '452001',
            'station_id' => $fromStation->id,
            'contact_person' => 'Admin',
            'contact_number' => '9876543210',
            'email' => 'ho@example.com',
            'has_gst' => true,
            'gst_number' => '23AGIPD3853A1Z1',
            'gst_type' => 'Forward',
            'gst_scheme' => 'Regular',
            'gst_slab' => 18,
            'gst_exemption_limit' => $options['gst_exemption_limit'] ?? 0,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo('view bilties');

        $consignor = Party::create(['company_id' => $company->id, 'name' => 'Consignor']);
        $consignee = Party::create(['company_id' => $company->id, 'name' => 'Consignee']);
        $good = Good::create(['owner_id' => $owner->id, 'name' => 'Goods']);
        $packageType = PackageType::create(['owner_id' => $owner->id, 'name' => 'Box']);

        $bilty = Bilty::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'bilty_number' => 'GST-1',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'payment_type' => 'To Pay',
            'freight' => $options['freight'] ?? 1500,
            'handling_charge' => $options['handling_charge'] ?? 50,
            'bilty_charge' => $options['bilty_charge'] ?? 20,
            'gst' => $options['gst'] ?? 18,
            'gst_liability_by' => $options['gst_liability_by'] ?? 'Consignor',
            'amount' => $options['amount'] ?? 1570,
            'created_by' => $user->id,
        ]);

        BiltyDetail::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'bilty_id' => $bilty->id,
            'good_id' => $good->id,
            'package_type_id' => $packageType->id,
            'no_of_packages' => 1,
            'gross_weight' => 10,
            'charged_weight' => 10,
            'rate' => 100,
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
