<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Agent;
use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\Party;
use App\Models\State;
use App\Models\Station;
use App\Models\User;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\Accounting\EntityAccountLinker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class QuickStoreAccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_party_quick_store_creates_ledger_account_when_considered_for_accounting(): void
    {
        $context = $this->context();

        $response = $this->actingAs($context['user'])->postJson(route('parties.quick-store'), [
            ...$this->contactPayload($context),
            'name' => 'Quick Party',
            'consider_for_accounting' => true,
        ]);

        $response->assertCreated()->assertJsonPath('party.name', 'Quick Party');

        $party = Party::query()->withoutGlobalScopes()->where('name', 'Quick Party')->firstOrFail();
        $this->assertTrue($party->consider_for_accounting);

        $account = app(EntityAccountLinker::class)->linkedAccount($party);
        $this->assertNotNull($account);
        $this->assertTrue($account->is_selectable);
        $this->assertSame('sundry_debtors', $account->accountGroup->system_key);

        $response->assertJsonPath('party.account.id', $account->id)
            ->assertJsonPath('party.account.name', 'Quick Party')
            ->assertJsonPath('party.account.reference_type', 'Party');
    }

    public function test_party_quick_store_skips_ledger_account_by_default(): void
    {
        $context = $this->context();
        $accountsBefore = Account::query()->withoutGlobalScopes()->count();

        $response = $this->actingAs($context['user'])->postJson(route('parties.quick-store'), [
            ...$this->contactPayload($context),
            'name' => 'Plain Party',
        ]);

        $response->assertCreated()->assertJsonPath('party.account', null);

        $party = Party::query()->withoutGlobalScopes()->where('name', 'Plain Party')->firstOrFail();
        $this->assertFalse($party->consider_for_accounting);
        $this->assertNull(app(EntityAccountLinker::class)->linkedAccount($party));
        $this->assertSame($accountsBefore, Account::query()->withoutGlobalScopes()->count());
    }

    public function test_agent_quick_store_creates_ledger_account_when_considered_for_accounting(): void
    {
        $context = $this->context();

        $response = $this->actingAs($context['user'])->postJson(route('agents.quick-store'), [
            ...$this->contactPayload($context),
            'name' => 'Quick Agent',
            'consider_for_accounting' => true,
        ]);

        $response->assertCreated()->assertJsonPath('agent.name', 'Quick Agent');

        $agent = Agent::query()->withoutGlobalScopes()->where('name', 'Quick Agent')->firstOrFail();
        $this->assertTrue($agent->consider_for_accounting);

        $account = app(EntityAccountLinker::class)->linkedAccount($agent);
        $this->assertNotNull($account);
        $this->assertTrue($account->is_selectable);

        $response->assertJsonPath('agent.account.id', $account->id)
            ->assertJsonPath('agent.account.reference_type', 'Agent');
    }

    public function test_agent_quick_store_skips_ledger_account_by_default(): void
    {
        $context = $this->context();

        $response = $this->actingAs($context['user'])->postJson(route('agents.quick-store'), [
            ...$this->contactPayload($context),
            'name' => 'Plain Agent',
        ]);

        $response->assertCreated()->assertJsonPath('agent.account', null);

        $agent = Agent::query()->withoutGlobalScopes()->where('name', 'Plain Agent')->firstOrFail();
        $this->assertFalse($agent->consider_for_accounting);
        $this->assertNull(app(EntityAccountLinker::class)->linkedAccount($agent));
    }

    public function test_quick_store_rejects_invalid_consider_for_accounting_value(): void
    {
        $context = $this->context();

        $this->actingAs($context['user'])->postJson(route('parties.quick-store'), [
            ...$this->contactPayload($context),
            'name' => 'Bad Party',
            'consider_for_accounting' => 'maybe',
        ])->assertStatus(422)->assertJsonValidationErrors(['consider_for_accounting']);

        $this->actingAs($context['user'])->postJson(route('agents.quick-store'), [
            ...$this->contactPayload($context),
            'name' => 'Bad Agent',
            'consider_for_accounting' => 'maybe',
        ])->assertStatus(422)->assertJsonValidationErrors(['consider_for_accounting']);
    }

    /**
     * @param  array{state: State, city: City}  $context
     * @return array{address: string, state_id: int, city_id: int, mobile: string}
     */
    private function contactPayload(array $context): array
    {
        return [
            'address' => '1 Market Road',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9876543210',
        ];
    }

    /**
     * @return array{user: User, company: Company, state: State, city: City}
     */
    private function context(): array
    {
        foreach (['create parties', 'create agents'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Quick Co', 'user_id' => $owner->id]);
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
        $station = Station::create(['name' => 'UDR', 'owner_id' => $owner->id]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main',
            'address' => '1 St',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '313001',
            'station_id' => $station->id,
            'contact_person' => 'A',
            'contact_number' => '9000000000',
            'email' => 'a@example.com',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $user->givePermissionTo(['create parties', 'create agents']);

        return [
            'user' => $user,
            'company' => $company,
            'state' => $state,
            'city' => $city,
        ];
    }
}
