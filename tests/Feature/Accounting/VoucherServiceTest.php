<?php

namespace Tests\Feature\Accounting;

use App\Enums\AccountingVoucherType;
use App\Models\AccountingVoucher;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\EnsureCompanyAccounting;
use App\Services\Accounting\FinancialYearService;
use App\Services\Accounting\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VoucherServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_balanced_voucher_and_rejects_unbalanced(): void
    {
        $company = $this->makeCompany();
        app(EnsureCompanyAccounting::class)->handle($company);

        $cash = app(ChartOfAccountsService::class)->systemAccount($company, 'cash');
        $capital = app(ChartOfAccountsService::class)->systemAccount($company, 'owners_capital');
        $service = app(VoucherService::class);

        $voucher = $service->post([
            'company_id' => $company->id,
            'voucher_type' => AccountingVoucherType::Journal,
            'date' => now()->toDateString(),
            'narration' => 'Opening capital',
        ], [
            ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 1000],
        ]);

        $this->assertSame(AccountingVoucherType::Journal, $voucher->voucher_type);
        $this->assertCount(2, $voucher->entries);
        $this->assertEquals(1000.0, $voucher->totalDebit());
        $this->assertEquals(1000.0, $voucher->totalCredit());
        $this->assertStringStartsWith('JV-', $voucher->voucher_number);

        try {
            $service->post([
                'company_id' => $company->id,
                'voucher_type' => AccountingVoucherType::Journal,
                'date' => now()->toDateString(),
            ], [
                ['account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $capital->id, 'debit' => 0, 'credit' => 400],
            ]);
            $this->fail('Expected validation exception for unbalanced voucher.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('entries', $e->errors());
        }

        $this->assertSame(1, AccountingVoucher::query()->count());
    }

    public function test_rejects_inactive_and_cross_company_accounts(): void
    {
        $company = $this->makeCompany();
        $other = $this->makeCompany('Other Co');
        app(EnsureCompanyAccounting::class)->handle($company);
        app(EnsureCompanyAccounting::class)->handle($other);

        $cash = app(ChartOfAccountsService::class)->systemAccount($company, 'cash');
        $otherCash = app(ChartOfAccountsService::class)->systemAccount($other, 'cash');
        $capital = app(ChartOfAccountsService::class)->systemAccount($company, 'owners_capital');
        $service = app(VoucherService::class);

        $this->expectException(ValidationException::class);

        $service->post([
            'company_id' => $company->id,
            'voucher_type' => AccountingVoucherType::Journal,
            'date' => now()->toDateString(),
        ], [
            ['account_id' => $otherCash->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 100],
        ]);
    }

    public function test_cancel_and_reverse_preserve_history(): void
    {
        $company = $this->makeCompany();
        app(EnsureCompanyAccounting::class)->handle($company);

        $cash = app(ChartOfAccountsService::class)->systemAccount($company, 'cash');
        $capital = app(ChartOfAccountsService::class)->systemAccount($company, 'owners_capital');
        $service = app(VoucherService::class);

        $voucher = $service->post([
            'company_id' => $company->id,
            'voucher_type' => AccountingVoucherType::Opening,
            'date' => now()->toDateString(),
        ], [
            ['account_id' => $cash->id, 'debit' => 250, 'credit' => 0],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 250],
        ]);

        $service->cancel($voucher);
        $this->assertSame(2, $voucher->fresh()->entries()->count());
        $this->assertTrue($voucher->fresh()->status === \App\Enums\AccountingVoucherStatus::Cancelled);

        $posted = $service->post([
            'company_id' => $company->id,
            'voucher_type' => AccountingVoucherType::Journal,
            'date' => now()->toDateString(),
        ], [
            ['account_id' => $cash->id, 'debit' => 50, 'credit' => 0],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 50],
        ]);

        $reverse = $service->reverse($posted);
        $this->assertSame($posted->id, $reverse->reversed_voucher_id);
        $this->assertEquals(50.0, (float) $reverse->entries->sum('debit'));
        $this->assertSame(3, AccountingVoucher::query()->count());
    }

    public function test_duplicate_voucher_number_rejected(): void
    {
        $company = $this->makeCompany();
        app(EnsureCompanyAccounting::class)->handle($company);
        $fy = app(FinancialYearService::class)->ensureCurrent($company);

        $cash = app(ChartOfAccountsService::class)->systemAccount($company, 'cash');
        $capital = app(ChartOfAccountsService::class)->systemAccount($company, 'owners_capital');
        $service = app(VoucherService::class);

        $service->post([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'voucher_type' => AccountingVoucherType::Journal,
            'voucher_number' => 'JV-MANUAL-1',
            'date' => now()->toDateString(),
        ], [
            ['account_id' => $cash->id, 'debit' => 10, 'credit' => 0],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 10],
        ]);

        $this->expectException(ValidationException::class);

        $service->post([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'voucher_type' => AccountingVoucherType::Journal,
            'voucher_number' => 'JV-MANUAL-1',
            'date' => now()->toDateString(),
        ], [
            ['account_id' => $cash->id, 'debit' => 20, 'credit' => 0],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 20],
        ]);
    }

    private function makeCompany(string $name = 'Books Co'): Company
    {
        $owner = User::factory()->create();
        $company = Company::create(['name' => $name, 'user_id' => $owner->id]);

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
