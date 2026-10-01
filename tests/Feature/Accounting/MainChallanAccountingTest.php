<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountingVoucher;
use App\Models\Agent;
use App\Models\Branch;
use App\Models\Challan;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\MainChallan;
use App\Models\State;
use App\Models\Station;
use App\Models\Truck;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\Accounting\EntityAccountLinker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MainChallanAccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_saves_edited_charges_and_posts_payable_to_agent(): void
    {
        $context = $this->context();

        $this->actingAs($context['user'])
            ->post(route('main-challans.store'), $this->payload($context, [
                'labour_charge' => 150,
                'kat' => 25,
                'home_delivery_charge' => 40,
                'truck_freight' => 3000,
                'cash_received' => 200,
                'cash_paid' => 100,
                'total_amount' => 1685,
            ]))
            ->assertRedirect(route('main-challans.index'));

        $mainChallan = MainChallan::query()->latest('id')->firstOrFail();
        $this->assertEquals(150, $mainChallan->labour_charge);
        $this->assertEquals(25, $mainChallan->kat);
        $this->assertEquals(40, $mainChallan->home_delivery_charge);
        $this->assertEquals(3000, $mainChallan->truck_freight);
        $this->assertEquals(200, $mainChallan->cash_received);
        $this->assertEquals(100, $mainChallan->cash_paid);
        $this->assertSame($context['agent']->id, $mainChallan->pay_by_agent_id);

        $voucher = $this->postedVoucherFor($mainChallan);
        $this->assertNotNull($voucher);
        $this->assertSame('Main Challan MCH-ACC-1 agent settlement', $voucher->narration);

        $expense = app(ChartOfAccountsService::class)->systemAccount($context['company']->id, 'agent_challan_expense');
        $this->assertVoucherLine($voucher, $expense->id, 1685, 0);
        $this->assertVoucherLine($voucher, $context['agentAccount']->id, 0, 1685);
    }

    public function test_negative_total_posts_receivable_from_agent(): void
    {
        $context = $this->context();

        $this->actingAs($context['user'])
            ->post(route('main-challans.store'), $this->payload($context, ['total_amount' => -500]));

        $voucher = $this->postedVoucherFor(MainChallan::query()->latest('id')->firstOrFail());
        $income = app(ChartOfAccountsService::class)->systemAccount($context['company']->id, 'agent_challan_income');

        $this->assertVoucherLine($voucher, $context['agentAccount']->id, 500, 0);
        $this->assertVoucherLine($voucher, $income->id, 0, 500);
    }

    public function test_no_voucher_without_agent(): void
    {
        $context = $this->context();

        $this->actingAs($context['user'])
            ->post(route('main-challans.store'), $this->payload($context, [
                'pay_by_agent_id' => null,
                'total_amount' => 900,
            ]));

        $mainChallan = MainChallan::query()->latest('id')->firstOrFail();
        $this->assertNull($mainChallan->pay_by_agent_id);
        $this->assertNull($this->postedVoucherFor($mainChallan));
    }

    public function test_update_reverses_previous_voucher_and_posts_new_total(): void
    {
        $context = $this->context();

        $this->actingAs($context['user'])
            ->post(route('main-challans.store'), $this->payload($context, ['total_amount' => 1000]));
        $mainChallan = MainChallan::query()->latest('id')->firstOrFail();
        $firstVoucher = $this->postedVoucherFor($mainChallan);

        $this->actingAs($context['user'])
            ->put(route('main-challans.update', $mainChallan), $this->payload($context, [
                'truck_freight' => 400,
                'total_amount' => 600,
            ]))
            ->assertRedirect(route('main-challans.index'));

        $this->assertTrue($firstVoucher->fresh()->reversals()->exists());

        $newVoucher = $this->postedVoucherFor($mainChallan);
        $this->assertNotSame($firstVoucher->id, $newVoucher->id);
        $this->assertVoucherLine($newVoucher, $context['agentAccount']->id, 0, 600);
        $this->assertEquals(400, $mainChallan->fresh()->truck_freight);
    }

    public function test_destroy_reverses_voucher(): void
    {
        $context = $this->context();

        $this->actingAs($context['user'])
            ->post(route('main-challans.store'), $this->payload($context, ['total_amount' => 1000]));
        $mainChallan = MainChallan::query()->latest('id')->firstOrFail();
        $voucher = $this->postedVoucherFor($mainChallan);

        $this->actingAs($context['user'])
            ->delete(route('main-challans.destroy', $mainChallan))
            ->assertRedirect(route('main-challans.index'));

        $this->assertTrue($voucher->fresh()->reversals()->exists());
    }

    public function test_print_shows_total_with_account_name(): void
    {
        $context = $this->context();

        $this->actingAs($context['user'])
            ->post(route('main-challans.store'), $this->payload($context, ['total_amount' => 1250]));
        $mainChallan = MainChallan::query()->latest('id')->firstOrFail();

        $this->actingAs($context['user'])
            ->get(route('main-challans.print', $mainChallan))
            ->assertOk()
            ->assertSee('1,250.00 Dr To Settlement Agent', false);
    }

    private function postedVoucherFor(MainChallan $mainChallan): ?AccountingVoucher
    {
        return AccountingVoucher::query()
            ->where('source_type', MainChallan::class)
            ->where('source_id', $mainChallan->id)
            ->posted()
            ->whereNull('reversed_voucher_id')
            ->whereDoesntHave('reversals')
            ->with('entries')
            ->latest('id')
            ->first();
    }

    private function assertVoucherLine(AccountingVoucher $voucher, int $accountId, float $debit, float $credit): void
    {
        $line = $voucher->entries->firstWhere('account_id', $accountId);

        $this->assertNotNull($line, "Missing voucher line for account {$accountId}.");
        $this->assertEquals($debit, (float) $line->debit);
        $this->assertEquals($credit, (float) $line->credit);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $context, array $overrides = []): array
    {
        return [
            'main_challan_number' => 'MCH-ACC-1',
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'truck_id' => $context['truck']->id,
            'driver_name' => 'Acc Driver',
            'pay_by_agent_id' => $context['agent']->id,
            'details' => [['challan_id' => $context['challan']->id]],
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function context(): array
    {
        foreach (['view challans', 'create challans', 'edit challans', 'delete challans'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Main Challan Acc Co', 'user_id' => $owner->id]);
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
        app(EnsureCompanyAccounting::class)->handle($company);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['country_id' => $country->id, 'name' => 'RJ', 'code' => 'RJ']);
        $city = City::create(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Udaipur']);
        $fromStation = Station::create(['name' => 'Acc From', 'owner_id' => $owner->id]);
        $toStation = Station::create(['name' => 'Acc To', 'owner_id' => $owner->id]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main',
            'address' => '1 St',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '313001',
            'station_id' => $fromStation->id,
            'contact_person' => 'A',
            'contact_number' => '9000000000',
            'email' => 'acc@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo(['view challans', 'create challans', 'edit challans', 'delete challans']);

        $truck = Truck::create(['company_id' => $company->id, 'truck_number' => 'RJ27ACC1']);

        $agent = Agent::create([
            'company_id' => $company->id,
            'name' => 'Settlement Agent',
            'address' => '2 Market',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile' => '9000000001',
            'consider_for_accounting' => true,
        ]);
        $agentAccount = app(EntityAccountLinker::class)->enable($agent);

        $challan = Challan::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'challan_number' => 'CH-ACC-1',
            'date' => now()->toDateString(),
            'from_station_id' => $fromStation->id,
            'to_station_id' => $toStation->id,
            'truck_id' => $truck->id,
            'driver_name' => 'Acc Driver',
            'status' => 'Created',
        ]);

        return [
            'user' => $user,
            'company' => $company,
            'fromStation' => $fromStation,
            'toStation' => $toStation,
            'truck' => $truck,
            'agent' => $agent,
            'agentAccount' => $agentAccount,
            'challan' => $challan,
        ];
    }
}
