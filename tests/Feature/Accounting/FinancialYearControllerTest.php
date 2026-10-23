<?php

namespace Tests\Feature\Accounting;

use App\Models\FinancialYear;
use App\Services\Accounting\FinancialYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class FinancialYearControllerTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    private const ALL = ['view accounting_settings', 'delete accounting_settings'];

    public function test_index_lists_financial_years_newest_first_with_voucher_counts(): void
    {
        $context = $this->accountingContext(self::ALL);
        app(FinancialYearService::class)->forDate($context['company'], now()->subYears(2));

        $this->actingAs($context['user'])
            ->get(route('accounting.financial-years.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/FinancialYears/Index')
                ->has('financialYears', 2)
                ->where('financialYears.0.is_current', true)
                ->where('financialYears.0.vouchers_count', 0)
                ->where('financialYears.1.is_current', false));
    }

    public function test_index_requires_permission(): void
    {
        $context = $this->accountingContext(['delete accounting_settings']);

        $this->actingAs($context['user'])
            ->get(route('accounting.financial-years.index'))
            ->assertForbidden();
    }

    public function test_close_marks_year_closed_and_redirects_back_with_message(): void
    {
        $context = $this->accountingContext(self::ALL);
        $year = app(FinancialYearService::class)->ensureCurrent($context['company']);

        $this->actingAs($context['user'])
            ->from(route('accounting.financial-years.index'))
            ->post(route('accounting.financial-years.close', $year))
            ->assertRedirect(route('accounting.financial-years.index'))
            ->assertSessionHas('success', "{$year->name} closed. No entries can be posted or changed in it until it is reopened.");

        $year->refresh();
        $this->assertTrue($year->is_closed);
        $this->assertSame($context['user']->id, $year->closed_by);
        $this->assertNotNull($year->closed_at);
    }

    public function test_reopen_clears_closed_state(): void
    {
        $context = $this->accountingContext(self::ALL);
        $years = app(FinancialYearService::class);
        $year = $years->ensureCurrent($context['company']);
        $years->close($year, $context['user']->id);

        $this->actingAs($context['user'])
            ->from(route('accounting.financial-years.index'))
            ->post(route('accounting.financial-years.reopen', $year))
            ->assertRedirect(route('accounting.financial-years.index'))
            ->assertSessionHas('success', "{$year->name} reopened.");

        $year->refresh();
        $this->assertFalse($year->is_closed);
        $this->assertNull($year->closed_by);
        $this->assertNull($year->closed_at);
    }

    public function test_close_and_reopen_require_permission(): void
    {
        $context = $this->accountingContext(['view accounting_settings']);
        $year = app(FinancialYearService::class)->ensureCurrent($context['company']);

        $this->actingAs($context['user'])
            ->post(route('accounting.financial-years.close', $year))
            ->assertForbidden();

        $this->actingAs($context['user'])
            ->post(route('accounting.financial-years.reopen', $year))
            ->assertForbidden();

        $this->assertFalse($year->fresh()->is_closed);
    }

    public function test_cannot_close_another_companys_year(): void
    {
        $context = $this->accountingContext(self::ALL);
        $other = $this->accountingContext([], 'B');
        $foreignYear = app(FinancialYearService::class)->ensureCurrent($other['company']);

        $this->actingAs($context['user'])
            ->post(route('accounting.financial-years.close', $foreignYear))
            ->assertNotFound();

        $this->assertFalse(FinancialYear::query()->withoutGlobalScopes()->findOrFail($foreignYear->id)->is_closed);
    }
}
