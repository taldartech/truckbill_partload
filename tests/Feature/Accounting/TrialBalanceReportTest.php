<?php

namespace Tests\Feature\Accounting;

use App\Enums\AccountingVoucherType;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\Accounting\Reports\TrialBalanceReport;
use App\Services\Accounting\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialBalanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_balance_totals_match(): void
    {
        $owner = User::factory()->create();
        $company = Company::create(['name' => 'TB Co', 'user_id' => $owner->id]);
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
        $cash = app(ChartOfAccountsService::class)->systemAccount($company, 'cash');
        $capital = app(ChartOfAccountsService::class)->systemAccount($company, 'owners_capital');

        app(VoucherService::class)->post([
            'company_id' => $company->id,
            'voucher_type' => AccountingVoucherType::Opening,
            'date' => now()->toDateString(),
        ], [
            ['account_id' => $cash->id, 'debit' => 5000, 'credit' => 0],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 5000],
        ]);

        $report = app(TrialBalanceReport::class)->build(
            $company->id,
            now()->startOfMonth()->toDateString(),
            now()->toDateString(),
        );

        $this->assertEquals($report['totals']['debit'], $report['totals']['credit']);
        $this->assertGreaterThan(0, $report['totals']['debit']);
    }
}
