<?php

namespace Tests\Feature\Ops;

use App\Enums\AccountingVoucherType;
use App\Models\Account;
use App\Models\BankAccount;
use App\Services\Accounting\EntityAccountLinker;
use App\Services\Accounting\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class BankAccountCrudTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    private const ALL = ['view bank_accounts', 'create bank_accounts', 'edit bank_accounts', 'delete bank_accounts'];

    public function test_index_lists_bank_accounts_and_supports_search(): void
    {
        $context = $this->accountingContext(self::ALL);
        $this->makeBank($context, 'HDFC Current', 'HDFC Bank', '111222', 'HDFC0001');
        $this->makeBank($context, 'SBI Savings', 'State Bank', '999888', 'SBIN0002');

        $this->actingAs($context['user'])
            ->get(route('bank-accounts.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('BankAccounts/Index')
                ->has('bankAccounts.data', 2)
                ->where('filters.search', ''));

        foreach (['HDFC Current', 'State Bank', '999888', 'SBIN0002'] as $term) {
            $this->actingAs($context['user'])
                ->get(route('bank-accounts.index', ['search' => $term]))
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->has('bankAccounts.data', 1)
                    ->where('filters.search', $term));
        }
    }

    public function test_index_rejects_overlong_search_and_requires_permission(): void
    {
        $context = $this->accountingContext(self::ALL);

        $this->actingAs($context['user'])
            ->from(route('bank-accounts.index'))
            ->get(route('bank-accounts.index', ['search' => str_repeat('a', 101)]))
            ->assertSessionHasErrors('search');

        $other = $this->accountingContext(['create bank_accounts'], 'B');
        $this->actingAs($other['user'])
            ->get(route('bank-accounts.index'))
            ->assertForbidden();
    }

    public function test_index_is_company_scoped(): void
    {
        $context = $this->accountingContext(self::ALL);
        $other = $this->accountingContext([], 'B');
        $this->makeBank($context, 'Mine');
        $this->makeBank($other, 'Theirs');

        $this->actingAs($context['user'])
            ->get(route('bank-accounts.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('bankAccounts.data', 1)
                ->where('bankAccounts.data.0.name', 'Mine'));
    }

    public function test_create_and_edit_redirect_to_index(): void
    {
        $context = $this->accountingContext(self::ALL);
        $bank = $this->makeBank($context, 'Redirector');

        $this->actingAs($context['user'])
            ->get(route('bank-accounts.create'))
            ->assertRedirect(route('bank-accounts.index'));

        $this->actingAs($context['user'])
            ->get(route('bank-accounts.edit', $bank))
            ->assertRedirect(route('bank-accounts.index'));
    }

    public function test_create_and_edit_require_permission(): void
    {
        $context = $this->accountingContext(['view bank_accounts']);
        $bank = $this->makeBank($context, 'Guarded');

        $this->actingAs($context['user'])
            ->get(route('bank-accounts.create'))
            ->assertForbidden();

        $this->actingAs($context['user'])
            ->get(route('bank-accounts.edit', $bank))
            ->assertForbidden();
    }

    public function test_show_renders_bank_account_with_ledger(): void
    {
        $context = $this->accountingContext(self::ALL);
        $bank = $this->makeBank($context, 'Shown Bank');

        $this->actingAs($context['user'])
            ->get(route('bank-accounts.show', $bank))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('BankAccounts/Show')
                ->where('bankAccount.id', $bank->id)
                ->where('bankAccount.company.id', $context['company']->id)
                ->where('bankAccount.account.bank_account_id', $bank->id));
    }

    public function test_show_requires_permission_and_hides_other_companies(): void
    {
        $context = $this->accountingContext(['create bank_accounts']);
        $bank = $this->makeBank($context, 'Private Bank');

        $this->actingAs($context['user'])
            ->get(route('bank-accounts.show', $bank))
            ->assertForbidden();

        $viewer = $this->accountingContext(['view bank_accounts'], 'B');
        $this->actingAs($viewer['user'])
            ->get(route('bank-accounts.show', $bank))
            ->assertNotFound();
    }

    public function test_store_creates_bank_account_and_chart_ledger(): void
    {
        $context = $this->accountingContext(self::ALL);

        $this->actingAs($context['user'])
            ->from(route('bank-accounts.index'))
            ->post(route('bank-accounts.store'), [
                'name' => 'Axis Current',
                'bank_name' => 'Axis',
                'account_number' => '555',
                'ifsc_code' => 'UTIB0001',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('bank-accounts.index'))
            ->assertSessionHas('success', 'Bank account created successfully.');

        $bank = BankAccount::query()->where('name', 'Axis Current')->firstOrFail();
        $this->assertSame($context['company']->id, $bank->company_id);
        $this->assertTrue($bank->is_active);

        $ledger = Account::query()->where('bank_account_id', $bank->id)->firstOrFail();
        $this->assertSame('Axis Current', $ledger->name);
        $this->assertTrue($ledger->is_active);
        $this->assertSame('bank_accounts', $ledger->accountGroup->system_key);
    }

    public function test_store_ignores_client_supplied_company_and_branch(): void
    {
        $context = $this->accountingContext(self::ALL);
        $other = $this->accountingContext([], 'B');

        $this->actingAs($context['user'])
            ->post(route('bank-accounts.store'), [
                'name' => 'Injected',
                'company_id' => $other['company']->id,
                'branch_id' => $other['branch']->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bank_accounts', ['name' => 'Injected', 'company_id' => $context['company']->id]);
    }

    public function test_store_honours_inactive_flag(): void
    {
        $context = $this->accountingContext(self::ALL);

        $this->actingAs($context['user'])
            ->post(route('bank-accounts.store'), ['name' => 'Dormant', 'is_active' => false])
            ->assertSessionHasNoErrors();

        $bank = BankAccount::query()->where('name', 'Dormant')->firstOrFail();
        $this->assertFalse($bank->is_active);
        $this->assertFalse(Account::query()->where('bank_account_id', $bank->id)->firstOrFail()->is_selectable);
    }

    public function test_store_validates_and_requires_permission(): void
    {
        $context = $this->accountingContext(self::ALL);

        $this->actingAs($context['user'])
            ->post(route('bank-accounts.store'), [])
            ->assertSessionHasErrors('name');

        $this->actingAs($context['user'])
            ->post(route('bank-accounts.store'), [
                'name' => str_repeat('n', 256),
                'bank_name' => str_repeat('b', 256),
                'account_number' => str_repeat('1', 51),
                'ifsc_code' => str_repeat('I', 21),
                'is_active' => 'maybe',
            ])
            ->assertSessionHasErrors(['name', 'bank_name', 'account_number', 'ifsc_code', 'is_active']);

        $viewer = $this->accountingContext(['view bank_accounts'], 'B');
        $this->actingAs($viewer['user'])
            ->post(route('bank-accounts.store'), ['name' => 'Blocked'])
            ->assertForbidden();

        $this->assertDatabaseCount('bank_accounts', 0);
    }

    public function test_update_changes_bank_and_syncs_ledger(): void
    {
        $context = $this->accountingContext(self::ALL);
        $bank = $this->makeBank($context, 'Old Name');

        $this->actingAs($context['user'])
            ->put(route('bank-accounts.update', $bank), [
                'name' => 'New Name',
                'bank_name' => 'New Bank',
                'is_active' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Bank account updated successfully.');

        $bank->refresh();
        $this->assertSame('New Name', $bank->name);
        $this->assertFalse($bank->is_active);

        $ledger = Account::query()->where('bank_account_id', $bank->id)->firstOrFail();
        $this->assertSame('New Name', $ledger->name);
        $this->assertFalse($ledger->is_active);
    }

    public function test_update_validates_and_requires_permission(): void
    {
        $context = $this->accountingContext(self::ALL);
        $bank = $this->makeBank($context, 'Stable');

        $this->actingAs($context['user'])
            ->put(route('bank-accounts.update', $bank), ['name' => ''])
            ->assertSessionHasErrors('name');

        $viewer = $this->accountingContext(['view bank_accounts'], 'B');
        $viewerBank = $this->makeBank($viewer, 'Viewer Bank');
        $this->actingAs($viewer['user'])
            ->put(route('bank-accounts.update', $viewerBank), ['name' => 'Nope'])
            ->assertForbidden();

        $this->assertSame('Stable', $bank->fresh()->name);
        $this->assertSame('Viewer Bank', $viewerBank->fresh()->name);
    }

    public function test_update_cannot_touch_other_companys_bank(): void
    {
        $context = $this->accountingContext(self::ALL);
        $other = $this->accountingContext([], 'B');
        $foreign = $this->makeBank($other, 'Foreign Bank');

        $this->actingAs($context['user'])
            ->put(route('bank-accounts.update', $foreign), ['name' => 'Hijacked'])
            ->assertNotFound();

        $this->assertSame('Foreign Bank', $foreign->fresh()->name);
    }

    public function test_destroy_soft_deletes_and_deactivates_unused_ledger(): void
    {
        $context = $this->accountingContext(self::ALL);
        $bank = $this->makeBank($context, 'Closing Bank');

        $this->actingAs($context['user'])
            ->delete(route('bank-accounts.destroy', $bank), ['delete_remark' => 'Account closed'])
            ->assertRedirect(route('bank-accounts.index'))
            ->assertSessionHas('success', 'Bank account deleted successfully.');

        $this->assertSoftDeleted('bank_accounts', ['id' => $bank->id]);
        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bank->id,
            'delete_remark' => 'Account closed',
            'deleted_by' => $context['user']->id,
        ]);

        $ledger = Account::query()->where('bank_account_id', $bank->id)->firstOrFail();
        $this->assertFalse($ledger->is_selectable);
        $this->assertFalse($ledger->is_active);
    }

    public function test_destroy_keeps_ledger_active_when_it_has_posted_history(): void
    {
        $context = $this->accountingContext(self::ALL);
        $bank = $this->makeBank($context, 'Busy Bank');
        $ledger = Account::query()->where('bank_account_id', $bank->id)->firstOrFail();

        $this->actingAs($context['user']);
        app(VoucherService::class)->post([
            'company_id' => $context['company']->id,
            'voucher_type' => AccountingVoucherType::Journal,
            'date' => now()->toDateString(),
        ], [
            ['account_id' => $ledger->id, 'debit' => 50, 'credit' => 0],
            ['account_id' => $this->systemAccountId($context['company'], 'cash'), 'debit' => 0, 'credit' => 50],
        ]);

        $this->delete(route('bank-accounts.destroy', $bank))
            ->assertRedirect(route('bank-accounts.index'));

        $ledger->refresh();
        $this->assertTrue($ledger->is_active);
        $this->assertFalse($ledger->is_selectable);
    }

    public function test_destroy_requires_permission(): void
    {
        $context = $this->accountingContext(['view bank_accounts', 'edit bank_accounts']);
        $bank = $this->makeBank($context, 'Protected');

        $this->actingAs($context['user'])
            ->delete(route('bank-accounts.destroy', $bank))
            ->assertForbidden();

        $this->assertNotSoftDeleted('bank_accounts', ['id' => $bank->id]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeBank(
        array $context,
        string $name,
        ?string $bankName = null,
        ?string $accountNumber = null,
        ?string $ifsc = null,
    ): BankAccount {
        $bank = BankAccount::create([
            'company_id' => $context['company']->id,
            'name' => $name,
            'bank_name' => $bankName,
            'account_number' => $accountNumber,
            'ifsc_code' => $ifsc,
            'is_active' => true,
        ]);

        app(EntityAccountLinker::class)->syncBankAccount($bank);

        return $bank;
    }
}
