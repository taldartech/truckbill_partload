<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\Accounting\EntityAccountLinker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountingModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_account_crud_creates_chart_account(): void
    {
        foreach (['create bank_accounts', 'view bank_accounts', 'edit bank_accounts', 'delete bank_accounts'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Accounting Co', 'user_id' => $owner->id]);
        app(EnsureCompanyAccounting::class)->handle($company);
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

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo(['create bank_accounts', 'view bank_accounts', 'edit bank_accounts', 'delete bank_accounts']);

        $response = $this->actingAs($user)->post(route('bank-accounts.store'), [
            'name' => 'HDFC Current',
            'bank_name' => 'HDFC',
            'account_number' => '123456',
            'ifsc_code' => 'HDFC0001',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $bank = BankAccount::query()->first();
        $this->assertNotNull($bank);

        $account = app(EntityAccountLinker::class)->syncBankAccount($bank);
        $this->assertSame($bank->id, $account->bank_account_id);
        $this->assertSame('bank_accounts', $account->accountGroup->system_key);
    }

    public function test_legacy_voucher_routes_redirect_to_accounting(): void
    {
        Permission::findOrCreate('view vouchers');
        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Redirect Co', 'user_id' => $owner->id]);
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
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('view vouchers');

        $this->actingAs($user)->get(route('vouchers.index'))->assertRedirect(route('accounting.vouchers.index'));
    }
}
