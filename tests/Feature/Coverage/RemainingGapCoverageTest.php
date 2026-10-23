<?php

namespace Tests\Feature\Coverage;

use App\Models\Agent;
use App\Models\Bilty;
use App\Models\BiltyDetail;
use App\Models\Challan;
use App\Models\Company;
use App\Models\Good;
use App\Models\InwardRegister;
use App\Models\InwardRegisterDetail;
use App\Models\PackageType;
use App\Models\Party;
use App\Models\Truck;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\SubscriptionCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use ReflectionMethod;
use Tests\Concerns\CreatesTenantContext;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class RemainingGapCoverageTest extends TestCase
{
    use BuildsAccountingContext;
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_party_bulk_upload_requires_state_and_city_via_row_validator(): void
    {
        $context = $this->createTenantContext(['create parties']);

        $csv = "Party Name,Address,State,City,Mobile\n"
            ."Acme,1 Road,,,9876543210\n";
        $file = UploadedFile::fake()->createWithContent('parties.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('parties.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('The State field is required.', $response->json('errors.0.message'));
    }

    public function test_party_bulk_upload_requires_city_when_state_present(): void
    {
        $context = $this->createTenantContext(['create parties']);

        $csv = "Party Name,Address,State,City,Mobile\n"
            ."Acme,1 Road,Rajasthan,,9876543210\n";
        $file = UploadedFile::fake()->createWithContent('parties.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('parties.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $this->assertStringContainsString('The City field is required.', $response->json('errors.0.message'));
    }

    public function test_party_bulk_upload_nulls_blank_gst_and_email(): void
    {
        $context = $this->createTenantContext(['create parties']);

        $csv = "Party Name,Address,State,City,Mobile,Email,GST Number\n"
            .'Acme Party,1 Road,Rajasthan,Udaipur,9876543210, , '."\n";
        $file = UploadedFile::fake()->createWithContent('parties.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('parties.bulk-upload'), ['file' => $file]);

        $response->assertOk()->assertJsonPath('summary.successful', 1);
        $this->assertDatabaseHas('parties', [
            'name' => 'Acme Party',
            'email' => null,
            'gst_number' => null,
        ]);
    }

    public function test_agent_bulk_upload_requires_state(): void
    {
        $context = $this->createTenantContext(['create agents']);

        $csv = "Agent Name,Address,State,City,Mobile\n"
            ."Agent X,1 Road,,,9876543210\n";
        $file = UploadedFile::fake()->createWithContent('agents.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('agents.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $this->assertStringContainsString('The State field is required.', $response->json('errors.0.message'));
    }

    public function test_station_bulk_upload_requires_city_when_state_present(): void
    {
        $context = $this->createTenantContext(['create stations']);

        $csv = "Station Name,State,City\n"
            ."New Station,Rajasthan,\n";
        $file = UploadedFile::fake()->createWithContent('stations.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('stations.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $this->assertStringContainsString('The City field is required.', $response->json('errors.0.message'));
    }

    public function test_truck_bulk_upload_requires_truck_number_via_row_validator(): void
    {
        $context = $this->createTenantContext(['create trucks']);

        $csv = "Truck Number\n\n";
        $file = UploadedFile::fake()->createWithContent('trucks.csv', $csv);

        $response = $this->actingAs($context['user'])
            ->post(route('trucks.bulk-upload'), ['file' => $file]);

        $response->assertOk();
        $messages = collect($response->json('errors') ?? [])->pluck('message')->implode(' ');
        $this->assertTrue(
            str_contains($messages, 'Truck number is required') || ($response->json('summary.successful') ?? 1) === 0
        );
    }

    public function test_accounting_audit_fails_when_no_companies(): void
    {
        Company::query()->delete();

        $this->artisan('accounting:audit')->assertFailed();
    }

    public function test_subscription_product_display_names(): void
    {
        $service = app(SubscriptionCheckoutService::class);
        $method = new ReflectionMethod($service, 'productDisplayName');

        foreach (['broker' => 'Broker', 'ownfleet' => 'Ownfleet', 'parchun' => 'Parchun', 'other' => 'TruckBill'] as $product => $needle) {
            Config::set('subscription.product', $product);
            $this->assertStringContainsString($needle, $method->invoke($service));
        }
    }

    public function test_subscription_format_plan_and_charges_edge_branches(): void
    {
        $service = app(SubscriptionCheckoutService::class);

        $plan = new ReflectionMethod($service, 'formatPlanDetails');
        $formatted = $plan->invoke($service, [
            'catalog_snapshot' => [
                'included_branches' => 1,
                'users_per_branch' => 2,
                'included_lr_credits' => 10,
                'included_trucks' => 3,
            ],
            'quantities' => [
                'addon_branches' => 1,
                'addon_users' => 0,
                'addon_lr_credits' => 5,
                'addon_trucks' => 1,
            ],
            'proration' => ['remaining_days' => 12],
        ]);
        $this->assertStringContainsString('LR credits', $formatted);
        $this->assertStringContainsString('Trucks', $formatted);
        $this->assertStringContainsString('Prorated', $formatted);

        $charges = new ReflectionMethod($service, 'formatChargesBreakdown');
        $breakdown = $charges->invoke($service, [
            'line_items' => [
                ['amount' => 10],
                ['label' => 'Base', 'amount' => 100],
            ],
            'promo_snapshot' => ['code' => 'SAVE10'],
            'promo_discount' => 15,
            'gst_rate_percent' => 18,
            'gst_amount' => 18,
            'total_amount' => 103,
        ]);
        $this->assertStringContainsString('Promo discount', $breakdown);
    }

    public function test_invoice_covers_weight_and_package_rate_types(): void
    {
        $context = $this->accountingContext(['create invoices', 'view invoices', 'edit invoices']);
        app(EnsureCompanyAccounting::class)->handle($context['company']);

        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Invoice Party',
            'consider_for_accounting' => true,
        ]);
        $good = Good::create(['owner_id' => $context['owner']->id, 'name' => 'Goods']);
        $package = PackageType::create(['owner_id' => $context['owner']->id, 'name' => 'Box']);

        $bilty = Bilty::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_number' => 'BLT-RATE-1',
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'consignor_id' => $party->id,
            'consignee_id' => $party->id,
            'payment_type' => 'TBB',
            'freight' => 0,
            'amount' => 0,
            'status' => 'Pending',
            'gst' => 18,
        ]);

        $detailAttrs = [
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $bilty->id,
            'good_id' => $good->id,
            'package_type_id' => $package->id,
        ];
        $d1 = BiltyDetail::create($detailAttrs + [
            'no_of_packages' => 2, 'gross_weight' => 10, 'charged_weight' => 12, 'rate' => 5, 'rate_type' => 'Fixed',
        ]);
        $d2 = BiltyDetail::create($detailAttrs + [
            'no_of_packages' => 3, 'gross_weight' => 8, 'charged_weight' => 9, 'rate' => 4, 'rate_type' => 'Fixed',
        ]);
        $d3 = BiltyDetail::create($detailAttrs + [
            'no_of_packages' => 4, 'gross_weight' => 6, 'charged_weight' => 7, 'rate' => 3, 'rate_type' => 'Fixed',
        ]);

        $this->actingAs($context['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-RATE-1',
                'date' => now()->toDateString(),
                'party_id' => $party->id,
                'remark' => null,
                'bilty_ids' => [$bilty->id],
                'bilty_detail_freight' => [
                    (string) $bilty->id => [
                        ['id' => $d1->id, 'rate_type' => 'Per Actual Weight', 'rate' => 5],
                        ['id' => $d2->id, 'rate_type' => 'Per Charged Weight', 'rate' => 4],
                        ['id' => $d3->id, 'rate_type' => 'Per Package', 'rate' => 3],
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('invoices', ['invoice_no' => 'INV-RATE-1']);
    }

    public function test_invoice_rejects_when_bilty_ids_include_missing_id(): void
    {
        $context = $this->accountingContext(['create invoices']);
        $party = Party::create(['company_id' => $context['company']->id, 'name' => 'P']);

        $this->actingAs($context['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-MISS',
                'date' => now()->toDateString(),
                'party_id' => $party->id,
                'remark' => null,
                'bilty_ids' => [999999],
            ])
            ->assertSessionHasErrors();
    }

    public function test_challan_can_include_forward_inward_register_details(): void
    {
        $context = $this->accountingContext([
            'create challans',
            'edit challans',
            'view challans',
        ]);
        app(EnsureCompanyAccounting::class)->handle($context['company']);

        $party = Party::create(['company_id' => $context['company']->id, 'name' => 'IR Party']);
        $agent = Agent::create(['company_id' => $context['company']->id, 'name' => 'IR Agent']);
        $good = Good::create(['owner_id' => $context['owner']->id, 'name' => 'IR Good']);
        $truck = Truck::create([
            'company_id' => $context['company']->id,
            'truck_number' => 'RJ14GAP1',
        ]);

        $register = InwardRegister::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'inward_register_no' => 'IR-GAP-1',
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
        ]);

        $detail = InwardRegisterDetail::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'inward_register_id' => $register->id,
            'gr_no' => 'GR-GAP-1',
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'consignor_id' => $party->id,
            'consignee_id' => $party->id,
            'good_id' => $good->id,
            'no_of_packages' => 1,
            'weight' => 10,
            'freight' => 250,
            'agent_id' => $agent->id,
            'disposition' => 'forward',
            'status' => 'pending',
        ]);

        $payload = [
            'challan_number' => 'CH-GAP-1',
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'truck_id' => $truck->id,
            'driver_name' => 'Gap Driver',
            'details' => [
                ['inward_register_detail_id' => $detail->id],
            ],
        ];

        $this->actingAs($context['user'])
            ->post(route('challans.store'), $payload)
            ->assertSessionHasNoErrors();

        $challan = Challan::query()->where('challan_number', 'CH-GAP-1')->first();
        $this->assertNotNull($challan);
        $this->assertSame(1, $challan->details()->where('inward_register_detail_id', $detail->id)->count());
        $this->assertSame('challaned', $detail->fresh()->status);

        $this->actingAs($context['user'])
            ->put(route('challans.update', $challan), $payload)
            ->assertSessionHasNoErrors();
    }

    public function test_ensure_active_subscription_locked_mode_blocks_writes(): void
    {
        $context = $this->createTenantContext(
            ['view parties', 'create parties'],
            'Lock',
            'expired',
            now()->subMonths(2)->toDateString()
        );

        Cache::flush();
        Cache::put('subscription.settings', [
            'grace_period_days' => 0,
            'gst_rate_percent' => 18.0,
        ], now()->addHour());

        $this->actingAs($context['user'])
            ->post(route('parties.store'), [
                'name' => 'Blocked',
                'address' => 'x',
                'city_id' => $context['city']->id,
                'state_id' => $context['state']->id,
                'mobile' => '9876543210',
            ])
            ->assertRedirect();
    }
}
