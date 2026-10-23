<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class AccountGroupControllerTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    private const ALL = ['view account_groups', 'create account_groups', 'edit account_groups', 'delete account_groups'];

    public function test_index_lists_groups_natures_and_parent_options(): void
    {
        $context = $this->accountingContext(self::ALL);

        $this->actingAs($context['user'])
            ->get(route('accounting.account-groups.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/AccountGroups/Index')
                ->has('groups')
                ->has('natures', 5)
                ->where('natures.0.value', 'assets')
                ->where('natures.0.label', 'Assets')
                ->has('parentOptions'));
    }

    public function test_index_requires_permission(): void
    {
        $context = $this->accountingContext(['create account_groups']);

        $this->actingAs($context['user'])
            ->get(route('accounting.account-groups.index'))
            ->assertForbidden();
    }

    public function test_store_creates_group_with_parent(): void
    {
        $context = $this->accountingContext(self::ALL);
        $parent = $this->makeGroup($context, 'Parent Group');

        $this->actingAs($context['user'])
            ->from(route('accounting.account-groups.index'))
            ->post(route('accounting.account-groups.store'), [
                'name' => 'Child Group',
                'nature' => 'expenses',
                'parent_id' => $parent->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('accounting.account-groups.index'))
            ->assertSessionHas('success', 'Account group created.');

        $this->assertDatabaseHas('account_groups', [
            'company_id' => $context['company']->id,
            'name' => 'Child Group',
            'nature' => 'expenses',
            'parent_id' => $parent->id,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    public function test_store_validates_input(): void
    {
        $context = $this->accountingContext(self::ALL);
        $other = $this->accountingContext([], 'B');
        $foreign = AccountGroup::query()->withoutGlobalScopes()->where('company_id', $other['company']->id)->firstOrFail();

        $this->actingAs($context['user'])
            ->post(route('accounting.account-groups.store'), [])
            ->assertSessionHasErrors(['name', 'nature']);

        $this->actingAs($context['user'])
            ->post(route('accounting.account-groups.store'), ['name' => 'Bad', 'nature' => 'nonsense'])
            ->assertSessionHasErrors('nature');

        $this->actingAs($context['user'])
            ->post(route('accounting.account-groups.store'), ['name' => 'Bad', 'nature' => 'assets', 'parent_id' => $foreign->id])
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('account_groups', ['name' => 'Bad']);
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->accountingContext(['view account_groups']);

        $this->actingAs($context['user'])
            ->post(route('accounting.account-groups.store'), ['name' => 'Blocked', 'nature' => 'assets'])
            ->assertForbidden();
    }

    public function test_update_changes_custom_group(): void
    {
        $context = $this->accountingContext(self::ALL);
        $group = $this->makeGroup($context, 'Before');

        $this->actingAs($context['user'])
            ->put(route('accounting.account-groups.update', $group), [
                'name' => 'After',
                'nature' => 'income',
                'is_active' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Account group updated.');

        $group->refresh();
        $this->assertSame('After', $group->name);
        $this->assertSame('income', $group->nature->value);
        $this->assertFalse($group->is_active);
    }

    public function test_update_of_system_group_only_changes_name_and_active_flag(): void
    {
        $context = $this->accountingContext(self::ALL);
        $system = AccountGroup::query()->where('company_id', $context['company']->id)->where('is_system', true)->firstOrFail();
        $nature = $system->nature->value;
        $newNature = $nature === 'income' ? 'expenses' : 'income';

        $this->actingAs($context['user'])
            ->put(route('accounting.account-groups.update', $system), [
                'name' => 'Renamed System',
                'nature' => $newNature,
            ])
            ->assertSessionHasNoErrors();

        $system->refresh();
        $this->assertSame('Renamed System', $system->name);
        $this->assertSame($nature, $system->nature->value);
        $this->assertTrue($system->is_system);
    }

    public function test_update_rejects_self_as_parent_and_missing_fields(): void
    {
        $context = $this->accountingContext(self::ALL);
        $group = $this->makeGroup($context, 'Loop');

        $this->actingAs($context['user'])
            ->put(route('accounting.account-groups.update', $group), [
                'name' => 'Loop',
                'nature' => 'assets',
                'parent_id' => $group->id,
            ])
            ->assertSessionHasErrors('parent_id');

        $this->actingAs($context['user'])
            ->put(route('accounting.account-groups.update', $group), [])
            ->assertSessionHasErrors(['name', 'nature']);
    }

    public function test_update_requires_permission(): void
    {
        $context = $this->accountingContext(['view account_groups']);
        $group = $this->makeGroup($context, 'Locked');

        $this->actingAs($context['user'])
            ->put(route('accounting.account-groups.update', $group), ['name' => 'X', 'nature' => 'assets'])
            ->assertForbidden();
    }

    public function test_destroy_deletes_empty_custom_group(): void
    {
        $context = $this->accountingContext(self::ALL);
        $group = $this->makeGroup($context, 'Empty');

        $this->actingAs($context['user'])
            ->delete(route('accounting.account-groups.destroy', $group))
            ->assertSessionHas('success', 'Account group deleted.');

        $this->assertDatabaseMissing('account_groups', ['id' => $group->id]);
    }

    public function test_destroy_blocks_system_group(): void
    {
        $context = $this->accountingContext(self::ALL);
        $system = AccountGroup::query()->where('company_id', $context['company']->id)->where('is_system', true)->firstOrFail();

        $this->actingAs($context['user'])
            ->delete(route('accounting.account-groups.destroy', $system))
            ->assertSessionHas('error', 'System groups cannot be deleted.');

        $this->assertDatabaseHas('account_groups', ['id' => $system->id]);
    }

    public function test_destroy_blocks_group_with_accounts_or_children(): void
    {
        $context = $this->accountingContext(self::ALL);
        $withAccount = $this->makeGroup($context, 'Has Account');
        Account::create(['company_id' => $context['company']->id, 'name' => 'Inside', 'account_group_id' => $withAccount->id]);
        $withChild = $this->makeGroup($context, 'Has Child');
        $this->makeGroup($context, 'Kid', $withChild->id);

        foreach ([$withAccount, $withChild] as $group) {
            $this->actingAs($context['user'])
                ->delete(route('accounting.account-groups.destroy', $group))
                ->assertSessionHas('error', 'Remove child groups and accounts first.');

            $this->assertDatabaseHas('account_groups', ['id' => $group->id]);
        }
    }

    public function test_destroy_requires_permission(): void
    {
        $context = $this->accountingContext(['view account_groups']);
        $group = $this->makeGroup($context, 'Protected');

        $this->actingAs($context['user'])
            ->delete(route('accounting.account-groups.destroy', $group))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeGroup(array $context, string $name, ?int $parentId = null): AccountGroup
    {
        return AccountGroup::create([
            'company_id' => $context['company']->id,
            'name' => $name,
            'nature' => 'assets',
            'parent_id' => $parentId,
            'is_system' => false,
            'is_active' => true,
        ]);
    }
}
