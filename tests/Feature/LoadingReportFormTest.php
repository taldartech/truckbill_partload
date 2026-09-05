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
use App\Models\InwardRegister;
use App\Models\InwardRegisterDetail;
use App\Models\LoadingReport;
use App\Models\LoadingReportDetail;
use App\Models\PackageType;
use App\Models\Party;
use App\Models\State;
use App\Models\Station;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LoadingReportFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_includes_pending_bilties_and_inward_details(): void
    {
        $context = $this->createFormContext(['create loading_reports']);

        $response = $this->actingAs($context['user'])
            ->get(route('loading-reports.create'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('LoadingReports/Create')
            ->has('bilties', 1)
            ->where('bilties.0.id', $context['bilty']->id)
            ->has('inward_details', 1)
            ->where('inward_details.0.id', $context['inward']->id)
            ->etc()
        );
    }

    public function test_can_store_loading_report_with_bilty_and_inward_lines(): void
    {
        $context = $this->createFormContext(['create loading_reports']);

        $response = $this->actingAs($context['user'])
            ->post(route('loading-reports.store'), [
                'from_station_id' => $context['fromStation']->id,
                'to_station_id' => $context['toStation']->id,
                'truck_id' => $context['truck']->id,
                'driver_name' => 'Form Driver',
                'date' => now()->toDateString(),
                'remark' => 'Mixed lines',
                'details' => [
                    [
                        'row_type' => 'bilty',
                        'bilty_id' => $context['bilty']->id,
                        'inward_register_detail_id' => null,
                    ],
                    [
                        'row_type' => 'inward',
                        'bilty_id' => null,
                        'inward_register_detail_id' => $context['inward']->id,
                    ],
                ],
            ]);

        $response->assertRedirect(route('loading-reports.index'));

        $report = LoadingReport::query()->latest('id')->first();
        $this->assertNotNull($report);
        $this->assertSame(8, (int) $report->no_of_packages);
        $this->assertEquals(90.0, (float) $report->gross_weight);

        $this->assertDatabaseHas('loading_report_details', [
            'loading_report_id' => $report->id,
            'bilty_id' => $context['bilty']->id,
            'inward_register_detail_id' => null,
            'good_id' => $context['good']->id,
            'consignee_id' => $context['consignee']->id,
            'no_of_packages' => 5,
        ]);

        $this->assertDatabaseHas('loading_report_details', [
            'loading_report_id' => $report->id,
            'bilty_id' => null,
            'inward_register_detail_id' => $context['inward']->id,
            'good_id' => $context['good']->id,
            'consignee_id' => $context['consignee']->id,
            'no_of_packages' => 3,
        ]);
    }

    public function test_rejects_detail_row_without_bilty_or_inward(): void
    {
        $context = $this->createFormContext(['create loading_reports']);

        $response = $this->actingAs($context['user'])
            ->from(route('loading-reports.create'))
            ->post(route('loading-reports.store'), [
                'from_station_id' => $context['fromStation']->id,
                'to_station_id' => $context['toStation']->id,
                'truck_id' => $context['truck']->id,
                'driver_name' => 'Form Driver',
                'date' => now()->toDateString(),
                'details' => [
                    [
                        'row_type' => 'bilty',
                        'bilty_id' => null,
                        'inward_register_detail_id' => null,
                    ],
                ],
            ]);

        $response->assertRedirect(route('loading-reports.create'));
        $response->assertSessionHasErrors('details.0.bilty_id');
        $this->assertDatabaseCount('loading_reports', 0);
    }

    public function test_show_includes_inward_line_details(): void
    {
        $context = $this->createFormContext(['view loading_reports', 'create loading_reports']);

        $report = LoadingReport::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'truck_id' => $context['truck']->id,
            'driver_name' => 'Show Driver',
            'date' => now()->toDateString(),
            'no_of_packages' => 3,
            'gross_weight' => 40,
        ]);

        LoadingReportDetail::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'loading_report_id' => $report->id,
            'bilty_id' => null,
            'inward_register_detail_id' => $context['inward']->id,
            'good_id' => $context['good']->id,
            'package_type_id' => null,
            'consignee_id' => $context['consignee']->id,
            'no_of_packages' => 3,
            'gross_weight' => 40,
        ]);

        $response = $this->actingAs($context['user'])
            ->get(route('loading-reports.show', $report));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('LoadingReports/Show')
            ->has('report.details', 1)
            ->where('report.details.0.inward_register_detail.gr_no', 'GR-LR-200')
            ->where('report.details.0.good.name', 'Form Goods')
            ->etc()
        );
    }

    /**
     * @param  list<string>  $permissions
     * @return array{
     *     user: User,
     *     company: Company,
     *     branch: Branch,
     *     fromStation: Station,
     *     toStation: Station,
     *     truck: Truck,
     *     bilty: Bilty,
     *     inward: InwardRegisterDetail,
     *     good: Good,
     *     consignee: Party
     * }
     */
    private function createFormContext(array $permissions): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'LR Form Co',
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
        $fromStation = Station::create(['name' => 'From Form', 'owner_id' => $owner->id]);
        $toStation = Station::create(['name' => 'To Form', 'owner_id' => $owner->id]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Form Branch',
            'address' => '1 Form Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '400001',
            'station_id' => $fromStation->id,
            'contact_person' => 'Form Admin',
            'contact_number' => '9876543210',
            'email' => 'lr-form@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'MH-FORM-1',
        ]);

        $consignor = Party::create([
            'company_id' => $company->id,
            'name' => 'Form Consignor',
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);
        $consignee = Party::create([
            'company_id' => $company->id,
            'name' => 'Form Consignee',
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);
        $good = Good::create(['owner_id' => $owner->id, 'name' => 'Form Goods']);
        $packageType = PackageType::create(['owner_id' => $owner->id, 'name' => 'Bag']);

        $bilty = Bilty::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'bilty_number' => 'FORM-LR-100',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'payment_type' => 'To Pay',
            'freight' => 500,
            'amount' => 500,
            'status' => 'Pending',
        ]);

        BiltyDetail::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'bilty_id' => $bilty->id,
            'good_id' => $good->id,
            'package_type_id' => $packageType->id,
            'no_of_packages' => 5,
            'gross_weight' => 50,
        ]);

        $register = InwardRegister::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'date' => now()->subDay()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
        ]);

        $inward = InwardRegisterDetail::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inward_register_id' => $register->id,
            'gr_no' => 'GR-LR-200',
            'date' => now()->subDays(2)->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'good_id' => $good->id,
            'no_of_packages' => 3,
            'weight' => 40,
            'freight' => 300,
            'disposition' => 'forward',
            'status' => 'pending',
            'basis_of_booking' => 'To Pay',
        ]);

        return [
            'user' => $user,
            'company' => $company,
            'branch' => $branch,
            'fromStation' => $fromStation,
            'toStation' => $toStation,
            'truck' => $truck,
            'bilty' => $bilty,
            'inward' => $inward,
            'good' => $good,
            'consignee' => $consignee,
        ];
    }
}
