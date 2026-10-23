<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class AccountOptionsControllerTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('accounting.account-options'))->assertRedirect(route('login'));
    }

    public function test_returns_selectable_accounts_without_extra_permission(): void
    {
        $context = $this->accountingContext();

        $response = $this->actingAs($context['user'])
            ->getJson(route('accounting.account-options'))
            ->assertOk()
            ->assertJsonStructure(['accounts' => [['id', 'name', 'code', 'group_name', 'nature', 'reference_type', 'label']]]);

        $this->assertContains(
            $this->systemAccountId($context['company'], 'cash'),
            collect($response->json('accounts'))->pluck('id')->all()
        );
    }

    public function test_search_matches_name_code_and_group_name(): void
    {
        $context = $this->accountingContext();
        $group = AccountGroup::create([
            'company_id' => $context['company']->id,
            'name' => 'Quirky Group',
            'nature' => 'assets',
            'is_system' => false,
            'is_active' => true,
        ]);
        Account::create(['company_id' => $context['company']->id, 'name' => 'Needle Ledger', 'code' => 'NDL-9', 'account_group_id' => $group->id]);
        Account::create(['company_id' => $context['company']->id, 'name' => 'Plain Ledger', 'code' => 'PLN-1', 'account_group_id' => $group->id]);

        $names = fn (string $search) => collect($this->actingAs($context['user'])
            ->getJson(route('accounting.account-options', ['search' => $search]))
            ->assertOk()
            ->json('accounts'))->pluck('name')->all();

        $this->assertSame(['Needle Ledger'], $names('Needle'));
        $this->assertSame(['Needle Ledger'], $names('NDL-9'));
        $this->assertEqualsCanonicalizing(['Needle Ledger', 'Plain Ledger'], $names('Quirky'));
        $this->assertSame([], $names('zzz-no-match'));
    }

    public function test_excludes_inactive_and_non_selectable_accounts(): void
    {
        $context = $this->accountingContext();
        $group = AccountGroup::query()->where('company_id', $context['company']->id)->firstOrFail();
        Account::create(['company_id' => $context['company']->id, 'name' => 'Inactive Ledger', 'account_group_id' => $group->id, 'is_active' => false]);
        Account::create(['company_id' => $context['company']->id, 'name' => 'Hidden Ledger', 'account_group_id' => $group->id, 'is_selectable' => false]);

        $names = collect($this->actingAs($context['user'])
            ->getJson(route('accounting.account-options', ['search' => 'Ledger']))
            ->json('accounts'))->pluck('name');

        $this->assertFalse($names->contains('Inactive Ledger'));
        $this->assertFalse($names->contains('Hidden Ledger'));
    }

    public function test_results_are_limited_to_100_and_company_scoped(): void
    {
        $context = $this->accountingContext();
        $other = $this->accountingContext([], 'B');
        $group = AccountGroup::query()->where('company_id', $context['company']->id)->firstOrFail();
        $otherGroup = AccountGroup::query()->withoutGlobalScopes()->where('company_id', $other['company']->id)->firstOrFail();

        foreach (range(1, 105) as $i) {
            Account::create(['company_id' => $context['company']->id, 'name' => sprintf('Bulk %03d', $i), 'account_group_id' => $group->id]);
        }
        Account::create(['company_id' => $other['company']->id, 'name' => 'Bulk Foreign', 'account_group_id' => $otherGroup->id]);

        $accounts = collect($this->actingAs($context['user'])
            ->getJson(route('accounting.account-options', ['search' => 'Bulk']))
            ->json('accounts'));

        $this->assertCount(100, $accounts);
        $this->assertFalse($accounts->pluck('name')->contains('Bulk Foreign'));
    }
}
