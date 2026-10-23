<?php

namespace Tests\Feature\Accounting;

use App\Enums\AccountingVoucherType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Services\Accounting\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    private const ALL = ['view accounts', 'create accounts', 'edit accounts', 'delete accounts'];

    public function test_index_lists_accounts_with_groups(): void
    {
        $context = $this->accountingContext(self::ALL);

        $this->actingAs($context['user'])
            ->get(route('accounting.accounts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/Accounts/Index')
                ->has('accounts.data')
                ->has('groups')
                ->where('filters.search', ''));
    }

    public function test_index_search_filters_by_name_and_code(): void
    {
        $context = $this->accountingContext(self::ALL);
        $group = AccountGroup::query()->where('company_id', $context['company']->id)->firstOrFail();
        Account::create(['company_id' => $context['company']->id, 'name' => 'Zebra Ledger', 'code' => 'ZB-77', 'account_group_id' => $group->id]);

        $this->actingAs($context['user'])
            ->get(route('accounting.accounts.index', ['search' => 'Zebra']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('accounts.data', 1)
                ->where('accounts.data.0.name', 'Zebra Ledger')
                ->where('filters.search', 'Zebra'));

        $this->actingAs($context['user'])
            ->get(route('accounting.accounts.index', ['search' => 'ZB-77']))
            ->assertInertia(fn (Assert $page) => $page->has('accounts.data', 1));
    }

    public function test_index_requires_permission(): void
    {
        $context = $this->accountingContext(['create accounts']);

        $this->actingAs($context['user'])
            ->get(route('accounting.accounts.index'))
            ->assertForbidden();
    }

    public function test_show_renders_account_with_select_option(): void
    {
        $context = $this->accountingContext(self::ALL);
        $cashId = $this->systemAccountId($context['company'], 'cash');

        $this->actingAs($context['user'])
            ->get(route('accounting.accounts.show', $cashId))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/Accounts/Show')
                ->where('account.id', $cashId)
                ->has('account.select_option.label')
                ->has('account.account_group'));
    }

    public function test_show_requires_permission_and_hides_other_company_accounts(): void
    {
        $context = $this->accountingContext(['create accounts']);
        $cashId = $this->systemAccountId($context['company'], 'cash');

        $this->actingAs($context['user'])
            ->get(route('accounting.accounts.show', $cashId))
            ->assertForbidden();

        $viewer = $this->accountingContext(['view accounts'], 'B');
        $this->actingAs($viewer['user'])
            ->get(route('accounting.accounts.show', $cashId))
            ->assertNotFound();
    }

    public function test_store_creates_account_for_company(): void
    {
        $context = $this->accountingContext(self::ALL);
        $group = AccountGroup::query()->where('company_id', $context['company']->id)->firstOrFail();

        $this->actingAs($context['user'])
            ->from(route('accounting.accounts.index'))
            ->post(route('accounting.accounts.store'), [
                'name' => 'Petty Ledger',
                'code' => 'PL-1',
                'account_group_id' => $group->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('accounting.accounts.index'))
            ->assertSessionHas('success', 'Account created.');

        $this->assertDatabaseHas('accounts', [
            'company_id' => $context['company']->id,
            'name' => 'Petty Ledger',
            'code' => 'PL-1',
            'is_system' => false,
            'is_active' => true,
            'is_selectable' => true,
        ]);
    }

    public function test_store_honours_explicit_flags(): void
    {
        $context = $this->accountingContext(self::ALL);
        $group = AccountGroup::query()->where('company_id', $context['company']->id)->firstOrFail();

        $this->actingAs($context['user'])
            ->post(route('accounting.accounts.store'), [
                'name' => 'Hidden Ledger',
                'account_group_id' => $group->id,
                'is_active' => false,
                'is_selectable' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('accounts', [
            'name' => 'Hidden Ledger',
            'is_active' => false,
            'is_selectable' => false,
        ]);
    }

    public function test_store_validates_input_and_rejects_foreign_group(): void
    {
        $context = $this->accountingContext(self::ALL);
        $other = $this->accountingContext([], 'B');
        $foreignGroup = AccountGroup::query()->withoutGlobalScopes()->where('company_id', $other['company']->id)->firstOrFail();

        $this->actingAs($context['user'])
            ->post(route('accounting.accounts.store'), [])
            ->assertSessionHasErrors(['name', 'account_group_id']);

        $this->actingAs($context['user'])
            ->post(route('accounting.accounts.store'), [
                'name' => 'Cross Tenant',
                'account_group_id' => $foreignGroup->id,
            ])
            ->assertSessionHasErrors('account_group_id');

        $this->assertDatabaseMissing('accounts', ['name' => 'Cross Tenant']);
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->accountingContext(['view accounts']);
        $group = AccountGroup::query()->where('company_id', $context['company']->id)->firstOrFail();

        $this->actingAs($context['user'])
            ->post(route('accounting.accounts.store'), ['name' => 'Blocked', 'account_group_id' => $group->id])
            ->assertForbidden();

        $this->assertDatabaseMissing('accounts', ['name' => 'Blocked']);
    }

    public function test_update_changes_custom_account_fully(): void
    {
        $context = $this->accountingContext(self::ALL);
        $groups = AccountGroup::query()->where('company_id', $context['company']->id)->limit(2)->get();
        $account = Account::create([
            'company_id' => $context['company']->id,
            'name' => 'Old Name',
            'account_group_id' => $groups[0]->id,
            'is_system' => false,
        ]);

        $this->actingAs($context['user'])
            ->put(route('accounting.accounts.update', $account), [
                'name' => 'New Name',
                'code' => 'NN-1',
                'account_group_id' => $groups[1]->id,
                'is_active' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Account updated.');

        $account->refresh();
        $this->assertSame('New Name', $account->name);
        $this->assertSame('NN-1', $account->code);
        $this->assertSame($groups[1]->id, $account->account_group_id);
        $this->assertFalse($account->is_active);
    }

    public function test_update_of_system_account_only_changes_name_and_flags(): void
    {
        $context = $this->accountingContext(self::ALL);
        $cash = Account::query()->findOrFail($this->systemAccountId($context['company'], 'cash'));
        $otherGroup = AccountGroup::query()
            ->where('company_id', $context['company']->id)
            ->where('id', '!=', $cash->account_group_id)
            ->firstOrFail();
        $originalGroupId = $cash->account_group_id;

        $this->actingAs($context['user'])
            ->put(route('accounting.accounts.update', $cash), [
                'name' => 'Cash Renamed',
                'code' => 'HACKED',
                'account_group_id' => $otherGroup->id,
                'is_selectable' => false,
            ])
            ->assertSessionHasNoErrors();

        $cash->refresh();
        $this->assertSame('Cash Renamed', $cash->name);
        $this->assertSame($originalGroupId, $cash->account_group_id);
        $this->assertNotSame('HACKED', $cash->code);
        $this->assertFalse($cash->is_selectable);
        $this->assertTrue($cash->is_system);
    }

    public function test_update_validates_and_requires_permission(): void
    {
        $context = $this->accountingContext(['edit accounts']);
        $cashId = $this->systemAccountId($context['company'], 'cash');

        $this->actingAs($context['user'])
            ->put(route('accounting.accounts.update', $cashId), ['name' => ''])
            ->assertSessionHasErrors(['name', 'account_group_id']);

        $viewer = $this->accountingContext(['view accounts'], 'B');
        $this->actingAs($viewer['user'])
            ->put(route('accounting.accounts.update', $this->systemAccountId($viewer['company'], 'cash')), [
                'name' => 'Nope',
                'account_group_id' => 1,
            ])
            ->assertForbidden();
    }

    public function test_destroy_deletes_unused_custom_account(): void
    {
        $context = $this->accountingContext(self::ALL);
        $group = AccountGroup::query()->where('company_id', $context['company']->id)->firstOrFail();
        $account = Account::create(['company_id' => $context['company']->id, 'name' => 'Disposable', 'account_group_id' => $group->id]);

        $this->actingAs($context['user'])
            ->delete(route('accounting.accounts.destroy', $account))
            ->assertRedirect(route('accounting.accounts.index'))
            ->assertSessionHas('success', 'Account deleted.');

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    public function test_destroy_blocks_system_accounts(): void
    {
        $context = $this->accountingContext(self::ALL);
        $cashId = $this->systemAccountId($context['company'], 'cash');

        $this->actingAs($context['user'])
            ->from(route('accounting.accounts.index'))
            ->delete(route('accounting.accounts.destroy', $cashId))
            ->assertRedirect(route('accounting.accounts.index'))
            ->assertSessionHas('error', 'System accounts cannot be deleted.');

        $this->assertDatabaseHas('accounts', ['id' => $cashId]);
    }

    public function test_destroy_blocks_accounts_with_posted_history(): void
    {
        $context = $this->accountingContext(self::ALL);
        $group = AccountGroup::query()->where('company_id', $context['company']->id)->firstOrFail();
        $account = Account::create(['company_id' => $context['company']->id, 'name' => 'Used Ledger', 'account_group_id' => $group->id]);

        $this->actingAs($context['user']);
        app(VoucherService::class)->post([
            'company_id' => $context['company']->id,
            'voucher_type' => AccountingVoucherType::Journal,
            'date' => now()->toDateString(),
        ], [
            ['account_id' => $account->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $this->systemAccountId($context['company'], 'cash'), 'debit' => 0, 'credit' => 100],
        ]);

        $this->from(route('accounting.accounts.index'))
            ->delete(route('accounting.accounts.destroy', $account))
            ->assertRedirect(route('accounting.accounts.index'))
            ->assertSessionHas('error', 'Account has posted history and cannot be deleted.');

        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
    }

    public function test_destroy_requires_permission(): void
    {
        $context = $this->accountingContext(['view accounts']);

        $this->actingAs($context['user'])
            ->delete(route('accounting.accounts.destroy', $this->systemAccountId($context['company'], 'cash')))
            ->assertForbidden();
    }
}
