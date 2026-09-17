<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\EnsureCompanyAccounting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_default_groups_and_system_accounts(): void
    {
        $owner = User::factory()->create();
        $company = Company::create(['name' => 'CoA Co', 'user_id' => $owner->id]);
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

        $this->assertTrue(
            AccountGroup::query()->where('company_id', $company->id)->where('system_key', 'sundry_debtors')->exists()
        );
        $this->assertTrue(
            Account::query()->where('company_id', $company->id)->where('system_key', 'cash')->exists()
        );

        $cash = app(ChartOfAccountsService::class)->systemAccount($company, 'cash');
        $this->assertSame('Cash', $cash->name);
        $this->assertTrue($cash->accountGroup()->first()->nature === \App\Enums\AccountNature::Assets);

        // Idempotent
        app(EnsureCompanyAccounting::class)->handle($company);
        $this->assertSame(
            count(ChartOfAccountsService::SYSTEM_ACCOUNT_KEYS),
            Account::query()->where('company_id', $company->id)->where('is_system', true)->count()
        );
    }
}
