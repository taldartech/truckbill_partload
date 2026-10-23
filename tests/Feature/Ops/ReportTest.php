<?php

namespace Tests\Feature\Ops;

use App\Models\Bilty;
use App\Models\Challan;
use App\Models\Party;
use App\Models\Station;
use App\Models\Truck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_bilty_register_defaults_to_current_month(): void
    {

        $context = $this->createTenantContext(['view reports']);
        $current = $this->makeBilty($context, 'BLT-NOW');
        $this->makeBilty($context, 'BLT-OLD', now()->subMonths(3)->toDateString());

        $this->actingAs($context['user'])
            ->get(route('reports.bilty-register'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Reports/BiltyRegister')
                ->has('bilties', 1)
                ->where('bilties.0.id', $current->id)
                ->has('stations')
                ->has('parties', 2)
                ->where('filters.start_date', null)
                ->where('filters.party_id', null));
    }

    public function test_bilty_register_filters_by_date_range(): void
    {

        $context = $this->createTenantContext(['view reports']);
        $this->makeBilty($context, 'BLT-NOW');
        $old = $this->makeBilty($context, 'BLT-OLD', now()->subMonths(3)->toDateString());

        $this->actingAs($context['user'])
            ->get(route('reports.bilty-register', [
                'start_date' => now()->subMonths(4)->toDateString(),
                'end_date' => now()->subMonths(2)->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('bilties', 1)
                ->where('bilties.0.id', $old->id)
                ->where('filters.start_date', now()->subMonths(4)->toDateString()));
    }

    public function test_bilty_register_with_only_one_date_falls_back_to_current_month(): void
    {

        $context = $this->createTenantContext(['view reports']);
        $current = $this->makeBilty($context, 'BLT-NOW');
        $this->makeBilty($context, 'BLT-OLD', now()->subMonths(3)->toDateString());

        $this->actingAs($context['user'])
            ->get(route('reports.bilty-register', ['start_date' => now()->subYear()->toDateString()]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('bilties', 1)
                ->where('bilties.0.id', $current->id));
    }

    public function test_bilty_register_filters_by_station_and_party(): void
    {

        $context = $this->createTenantContext(['view reports']);
        $match = $this->makeBilty($context, 'BLT-MATCH');
        $otherStation = Station::create([
            'name' => 'Elsewhere',
            'owner_id' => $context['owner']->id,
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'country_id' => $context['country']->id,
        ]);
        $stranger = Party::create(['company_id' => $context['company']->id, 'name' => 'Stranger']);
        $this->makeBilty($context, 'BLT-OTHER', null, $otherStation, $stranger, $stranger);

        $this->actingAs($context['user'])
            ->get(route('reports.bilty-register', ['station_id' => $context['station']->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('bilties', 1)
                ->where('bilties.0.id', $match->id));

        $this->actingAs($context['user'])
            ->get(route('reports.bilty-register', ['station_id' => $otherStation->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('bilties', 1)->where('bilties.0.bilty_number', 'BLT-OTHER'));

        $this->actingAs($context['user'])
            ->get(route('reports.bilty-register', ['party_id' => $match->consignee_id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('bilties', 1)
                ->where('bilties.0.id', $match->id));

        $this->actingAs($context['user'])
            ->get(route('reports.bilty-register', ['party_id' => $stranger->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('bilties.0.bilty_number', 'BLT-OTHER'));
    }

    public function test_bilty_register_requires_permission_and_is_tenant_scoped(): void
    {
        $context = $this->createTenantContext(['view bilties']);

        $this->actingAs($context['user'])
            ->get(route('reports.bilty-register'))
            ->assertForbidden();

        $viewer = $this->createTenantContext(['view reports'], 'B');
        $other = $this->createTenantContext([], 'C');
        $this->makeBilty($other, 'BLT-FOREIGN');

        $this->actingAs($viewer['user'])
            ->get(route('reports.bilty-register'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('bilties', 0));
    }

    public function test_challan_register_defaults_and_filters_by_date(): void
    {
        $context = $this->createTenantContext(['view reports']);
        $current = $this->makeChallan($context, 'CH-NOW');
        $old = $this->makeChallan($context, 'CH-OLD', now()->subMonths(3)->toDateString());

        $this->actingAs($context['user'])
            ->get(route('reports.challan-register'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Reports/ChallanRegister')
                ->has('challans', 1)
                ->where('challans.0.id', $current->id)
                ->where('filters.start_date', null));

        $this->actingAs($context['user'])
            ->get(route('reports.challan-register', [
                'start_date' => now()->subMonths(4)->toDateString(),
                'end_date' => now()->subMonths(2)->toDateString(),
            ]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('challans', 1)
                ->where('challans.0.id', $old->id));
    }

    public function test_challan_register_requires_permission(): void
    {
        $context = $this->createTenantContext(['view challans']);

        $this->actingAs($context['user'])
            ->get(route('reports.challan-register'))
            ->assertForbidden();
    }

    public function test_legacy_ledger_reports_redirect_to_accounting_reports(): void
    {
        $context = $this->createTenantContext(['view accounting_reports']);

        $this->actingAs($context['user'])
            ->get(route('reports.party-ledger', ['party_id' => 5, 'from' => '2026-04-01']))
            ->assertRedirect(route('accounting.reports.receivables', ['party_id' => 5, 'from' => '2026-04-01']));

        $this->actingAs($context['user'])
            ->get(route('reports.vendor-ledger', ['ignored' => 1]))
            ->assertRedirect(route('accounting.reports.payables'));

        $this->actingAs($context['user'])
            ->get(route('reports.agent-ledger', ['agent_id' => 9]))
            ->assertRedirect(route('accounting.reports.payables', ['agent_id' => 9]));

        $this->actingAs($context['user'])
            ->get(route('reports.cash-bank-book', ['bank_id' => 3]))
            ->assertRedirect(route('accounting.reports.cash-book', ['bank_id' => 3]));
    }

    public function test_legacy_ledger_reports_accept_either_permission(): void
    {
        $context = $this->createTenantContext(['view reports']);

        $this->actingAs($context['user'])
            ->get(route('reports.party-ledger'))
            ->assertRedirect(route('accounting.reports.receivables'));
    }

    public function test_legacy_ledger_reports_require_permission(): void
    {
        $context = $this->createTenantContext(['view bilties']);

        foreach (['party-ledger', 'vendor-ledger', 'agent-ledger', 'cash-bank-book'] as $name) {
            $this->actingAs($context['user'])
                ->get(route("reports.{$name}"))
                ->assertForbidden();
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeBilty(
        array $context,
        string $number,
        ?string $date = null,
        ?Station $fromStation = null,
        ?Party $consignor = null,
        ?Party $consignee = null,
    ): Bilty {
        $consignor ??= Party::query()->firstOrCreate(
            ['company_id' => $context['company']->id, 'name' => 'Consignor'],
            ['state_id' => $context['state']->id, 'city_id' => $context['city']->id]
        );
        $consignee ??= Party::query()->firstOrCreate(
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
            'date' => $date ?? now()->toDateString(),
            'from_station_id' => ($fromStation ?? $context['station'])->id,
            'to_station_id' => $toStation->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'freight' => 100,
            'status' => 'Pending',
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeChallan(array $context, string $number, ?string $date = null): Challan
    {
        $truck = Truck::query()->firstOrCreate(
            ['company_id' => $context['company']->id, 'truck_number' => 'RJ27AB1234']
        );

        return Challan::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'challan_number' => $number,
            'date' => $date ?? now()->toDateString(),
            'from_station_id' => $context['station']->id,
            'to_station_id' => $context['station']->id,
            'truck_id' => $truck->id,
            'status' => 'Created',
        ]);
    }
}
