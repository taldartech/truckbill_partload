<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\FinancialYear;
use App\Models\User;
use App\Services\Accounting\FinancialYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancialYearServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ensure_current_auto_creates_indian_financial_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-17'));

        $company = $this->makeCompany();
        $fy = app(FinancialYearService::class)->ensureCurrent($company);

        $this->assertSame('FY 2026-27', $fy->name);
        $this->assertSame('2026-04-01', $fy->starts_on->toDateString());
        $this->assertSame('2027-03-31', $fy->ends_on->toDateString());
        $this->assertTrue($fy->is_current);
        $this->assertFalse($fy->is_closed);
        $this->assertSame(1, FinancialYear::query()->where('company_id', $company->id)->count());
    }

    public function test_ensure_current_marks_existing_year_as_current(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-17'));

        $company = $this->makeCompany();

        $stale = FinancialYear::query()->create([
            'company_id' => $company->id,
            'name' => 'FY 2025-26',
            'starts_on' => '2025-04-01',
            'ends_on' => '2026-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);

        $active = FinancialYear::query()->create([
            'company_id' => $company->id,
            'name' => 'FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => false,
            'is_closed' => false,
        ]);

        $fy = app(FinancialYearService::class)->ensureCurrent($company);

        $this->assertTrue($fy->is($active));
        $this->assertTrue($fy->fresh()->is_current);
        $this->assertFalse($fy->fresh()->is_closed);
        $this->assertFalse($stale->fresh()->is_current);
        $this->assertFalse($stale->fresh()->is_closed);
    }

    public function test_past_financial_year_stays_open_until_closed_manually(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-17'));

        $company = $this->makeCompany();
        $service = app(FinancialYearService::class);

        $past = $service->forDate($company, '2025-06-15');
        $service->ensureCurrent($company);

        $this->assertFalse($past->fresh()->is_closed);
        $service->assertOpen($past->fresh());
    }

    public function test_closed_year_rejects_postings_and_can_be_reopened(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-17'));

        $company = $this->makeCompany();
        $user = User::factory()->create();
        $service = app(FinancialYearService::class);

        $past = $service->close($service->forDate($company, '2025-06-15'), $user->id);

        $this->assertTrue($past->is_closed);
        $this->assertSame($user->id, $past->closed_by);
        $this->assertNotNull($past->closed_at);

        try {
            $service->assertOpen($past);
            $this->fail('Expected a closed year to reject postings.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('financial_year_id', $e->errors());
        }

        $reopened = $service->reopen($past);

        $this->assertFalse($reopened->is_closed);
        $this->assertNull($reopened->closed_by);
        $service->assertOpen($reopened);
    }

    public function test_for_date_auto_creates_year_for_voucher_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-17'));

        $company = $this->makeCompany();
        $fy = app(FinancialYearService::class)->forDate($company, '2025-06-15');

        $this->assertSame('FY 2025-26', $fy->name);
        $this->assertSame('2025-04-01', $fy->starts_on->toDateString());
    }

    private function makeCompany(): Company
    {
        $owner = User::factory()->create();
        $company = Company::create(['name' => 'FY Co', 'user_id' => $owner->id]);

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
