<?php

namespace Tests\Feature\Accounting;

use App\Enums\AccountingVoucherType;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Party;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\Accounting\EntityAccountLinker;
use App\Services\Accounting\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityAccountLinkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_enable_disable_preserves_history(): void
    {
        $company = $this->company();
        app(EnsureCompanyAccounting::class)->handle($company);

        $party = Party::create([
            'company_id' => $company->id,
            'name' => 'ABC Transport',
            'consider_for_accounting' => true,
        ]);

        $linker = app(EntityAccountLinker::class);
        $account = $linker->enable($party);
        $this->assertTrue($account->is_selectable);
        $this->assertSame('sundry_debtors', $account->accountGroup->system_key);

        $cash = app(ChartOfAccountsService::class)->systemAccount($company, 'cash');
        app(VoucherService::class)->post([
            'company_id' => $company->id,
            'voucher_type' => AccountingVoucherType::Journal,
            'date' => now()->toDateString(),
        ], [
            ['account_id' => $account->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => 100],
        ]);

        $party->update(['consider_for_accounting' => false]);
        $disabled = $linker->disable($party);
        $this->assertFalse($disabled->is_selectable);
        $this->assertTrue($disabled->is_active);
        $this->assertTrue(Account::query()->whereKey($account->id)->exists());
        $this->assertFalse($linker->deleteAccountIfSafe($disabled));
    }

    private function company(): Company
    {
        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Linker Co', 'user_id' => $owner->id]);
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

        return $company;
    }
}
