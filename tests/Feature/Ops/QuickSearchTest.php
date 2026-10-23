<?php

namespace Tests\Feature\Ops;

use App\Models\Bilty;
use App\Models\Challan;
use App\Models\ChallanDetail;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\MainChallan;
use App\Models\MainChallanDetail;
use App\Models\Party;
use App\Models\Station;
use App\Models\Truck;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class QuickSearchTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('quick-search.index'))->assertRedirect(route('login'));
    }

    public function test_empty_query_returns_no_results(): void
    {
        $context = $this->createTenantContext([]);
        $this->makeBilty($context, 'BLT-100');

        $this->actingAs($context['user'])
            ->get(route('quick-search.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('QuickSearch')
                ->where('query', '')
                ->has('bilties', 0)
                ->where('detail', null));
    }

    public function test_partial_query_lists_matching_bilties_without_detail(): void
    {
        $context = $this->createTenantContext([]);
        $this->makeBilty($context, 'BLT-100');
        $this->makeBilty($context, 'BLT-101');
        $this->makeBilty($context, 'XYZ-5');

        $this->actingAs($context['user'])
            ->get(route('quick-search.index', ['q' => ' BLT-10 ']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('query', 'BLT-10')
                ->has('bilties', 2)
                ->where('detail', null));
    }

    public function test_exact_match_loads_full_detail(): void
    {
        $context = $this->createTenantContext([]);
        $bilty = $this->makeBilty($context, 'BLT-200');

        $truck = Truck::create(['company_id' => $context['company']->id, 'truck_number' => 'RJ27AB1234']);
        $challan = Challan::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'challan_number' => 'CH-200',
            'date' => now()->toDateString(),
            'from_station_id' => $context['station']->id,
            'to_station_id' => $bilty->to_station_id,
            'truck_id' => $truck->id,
            'status' => 'Created',
        ]);
        ChallanDetail::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'challan_id' => $challan->id,
            'bilty_id' => $bilty->id,
            'to_station_id' => $bilty->to_station_id,
        ]);
        $mainChallan = MainChallan::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'main_challan_number' => 'MCH-200',
            'date' => now()->toDateString(),
            'from_station_id' => $context['station']->id,
            'to_station_id' => $bilty->to_station_id,
            'truck_id' => $truck->id,
            'total_amount' => 1000,
        ]);
        MainChallanDetail::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'main_challan_id' => $mainChallan->id,
            'challan_id' => $challan->id,
        ]);
        $invoice = Invoice::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => 'INV-200',
            'date' => now()->toDateString(),
            'party_id' => $bilty->consignor_id,
            'status' => 'Pending',
            'total_amount' => 100,
            'gst_amount' => 0,
            'net_amount' => 100,
        ]);
        InvoiceDetail::create(['invoice_id' => $invoice->id, 'bilty_id' => $bilty->id, 'amount' => 100]);
        $voucher = Voucher::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'voucher_number' => 'V-200',
            'date' => now()->toDateString(),
            'voucher_type' => 'Receipt',
            'amount' => 50,
            'mode' => 'cash',
            'accountable_type' => Party::class,
            'accountable_id' => $bilty->consignor_id,
        ]);

        $this->actingAs($context['user'])
            ->get(route('quick-search.index', ['q' => 'BLT-200']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('QuickSearch')
                ->has('bilties', 1)
                ->where('detail.bilty.id', $bilty->id)
                ->where('detail.bilty.consignor.name', 'Consignor')
                ->has('detail.challans', 1)
                ->where('detail.challans.0.id', $challan->id)
                ->has('detail.main_challans', 1)
                ->where('detail.main_challans.0.id', $mainChallan->id)
                ->has('detail.mainChallans', 1)
                ->has('detail.invoices', 1)
                ->where('detail.invoices.0.id', $invoice->id)
                ->has('detail.vouchers', 1)
                ->where('detail.vouchers.0.id', $voucher->id));
    }

    public function test_exact_match_without_related_records_has_empty_collections(): void
    {
        $context = $this->createTenantContext([]);
        $this->makeBilty($context, 'BLT-300');

        $this->actingAs($context['user'])
            ->get(route('quick-search.index', ['q' => 'BLT-300']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('detail.challans', 0)
                ->has('detail.main_challans', 0)
                ->has('detail.invoices', 0)
                ->has('detail.vouchers', 0));
    }

    public function test_search_does_not_leak_other_companys_bilties(): void
    {
        $context = $this->createTenantContext([]);
        $other = $this->createTenantContext([], 'B');
        $this->makeBilty($other, 'BLT-SECRET');

        $this->actingAs($context['user'])
            ->get(route('quick-search.index', ['q' => 'BLT-SECRET']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('bilties', 0)
                ->where('detail', null));
    }

    public function test_overlong_query_is_rejected(): void
    {
        $context = $this->createTenantContext([]);

        $this->actingAs($context['user'])
            ->from(route('quick-search.index'))
            ->get(route('quick-search.index', ['q' => str_repeat('a', 101)]))
            ->assertSessionHasErrors('q');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeBilty(array $context, string $number): Bilty
    {
        $consignor = Party::query()->firstOrCreate(
            ['company_id' => $context['company']->id, 'name' => 'Consignor'],
            ['state_id' => $context['state']->id, 'city_id' => $context['city']->id]
        );
        $consignee = Party::query()->firstOrCreate(
            ['company_id' => $context['company']->id, 'name' => 'Consignee'],
            ['state_id' => $context['state']->id, 'city_id' => $context['city']->id]
        );
        $toStation = Station::query()->withoutGlobalScopes()->firstOrCreate(
            ['owner_id' => $context['owner']->id, 'name' => 'Destination'],
            ['state_id' => $context['state']->id, 'city_id' => $context['city']->id, 'country_id' => $context['country']->id]
        );

        return Bilty::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_number' => $number,
            'date' => now()->toDateString(),
            'from_station_id' => $context['station']->id,
            'to_station_id' => $toStation->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'freight' => 100,
            'status' => 'Pending',
        ]);
    }
}
