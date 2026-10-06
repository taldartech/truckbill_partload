<?php

namespace Tests\Feature\Accounting;

use App\Enums\AccountingVoucherType;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Party;
use App\Services\Accounting\EntityAccountLinker;
use App\Services\Accounting\OperationalAccountingBridge;
use App\Services\Accounting\Reports\BalanceSheetReport;
use App\Services\Accounting\Reports\ExpenseReport;
use App\Services\Accounting\Reports\GstReport;
use App\Services\Accounting\Reports\ProfitAndLossReport;
use App\Services\Accounting\Reports\TdsReport;
use App\Services\Accounting\Reports\TrialBalanceReport;
use App\Services\Accounting\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class AccountingReportsTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    public function test_reports_reconcile_with_posted_documents(): void
    {
        $context = $this->accountingContext(['view accounting_reports']);
        $companyId = $context['company']->id;
        $party = $this->seedTransactions($context);
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        $trialBalance = app(TrialBalanceReport::class)->build($companyId, $from, $to);
        $this->assertEquals($trialBalance['totals']['debit'], $trialBalance['totals']['credit']);
        $this->assertEquals($trialBalance['totals']['period_debit'], $trialBalance['totals']['period_credit']);
        $this->assertNotEmpty($trialBalance['groups']);

        $pnl = app(ProfitAndLossReport::class)->build($companyId, $from, $to);
        $this->assertEquals(1000, $pnl['income_total']);
        $this->assertEquals(750, $pnl['expense_total']);
        $this->assertEquals(250, $pnl['net']);

        $balanceSheet = app(BalanceSheetReport::class)->build($companyId, $to, app(ProfitAndLossReport::class));
        $this->assertEquals($balanceSheet['totals']['assets'], $balanceSheet['totals']['liabilities_capital']);

        $gst = app(GstReport::class)->build($companyId, $from, $to);
        $this->assertEquals(180, $gst['totals']['output']);
        $this->assertEquals(180, $gst['totals']['net_payable']);
        $this->assertCount(2, $gst['rows']);
        $this->assertSame('Invoice', $gst['rows'][0]['source']['module']);

        $expenses = app(ExpenseReport::class)->build($companyId, $from, $to);
        $this->assertEquals(750, $expenses['total']);
        $this->assertSame([now()->format('Y-m')], $expenses['months']);

        $tds = app(TdsReport::class)->build($companyId, $from, $to);
        $this->assertEquals(20, $tds['totals']['receivable']);
        $this->assertSame(app(EntityAccountLinker::class)->linkedAccount($party)->id, $tds['rows'][0]['account_id']);
    }

    public function test_branch_filter_limits_report_figures(): void
    {
        $context = $this->accountingContext(['view accounting_reports']);
        $companyId = $context['company']->id;
        $otherBranch = Branch::create([...$context['branch']->only([
            'company_id', 'address', 'country_id', 'state_id', 'city_id', 'pin_code', 'station_id', 'contact_person', 'contact_number',
        ]), 'name' => 'Second', 'email' => 'second@example.com']);

        $this->postExpense($companyId, $context['branch']->id, 'general_expense', 300);
        $this->postExpense($companyId, $otherBranch->id, 'general_expense', 200);

        $report = app(ProfitAndLossReport::class);
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        $this->assertEquals(500, $report->build($companyId, $from, $to)['expense_total']);
        $this->assertEquals(300, $report->build($companyId, $from, $to, $context['branch']->id)['expense_total']);
        $this->assertEquals(200, $report->build($companyId, $from, $to, $otherBranch->id)['expense_total']);

        $this->actingAs($context['user'])
            ->get(route('accounting.reports.profit-and-loss', ['branch_id' => $otherBranch->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('branches', null)
                ->where('report.expense_total', 300));

        Role::findOrCreate(UserRole::Admin->value);
        $context['user']->assignRole(UserRole::Admin->value);

        $this->actingAs($context['user'])
            ->get(route('accounting.reports.profit-and-loss', ['branch_id' => $otherBranch->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('branches', 2)
                ->where('filters.branch_id', $otherBranch->id)
                ->where('report.expense_total', 200));

        $this->actingAs($context['user'])
            ->get(route('accounting.reports.profit-and-loss'))
            ->assertInertia(fn (Assert $page) => $page->where('report.expense_total', 500));
    }

    public function test_every_report_page_renders(): void
    {
        $context = $this->accountingContext(['view accounting_reports']);
        $party = $this->seedTransactions($context);
        $partyAccountId = app(EntityAccountLinker::class)->linkedAccount($party)->id;

        $pages = [
            'accounting.reports.trial-balance' => 'Accounting/Reports/TrialBalance',
            'accounting.reports.profit-and-loss' => 'Accounting/Reports/ProfitAndLoss',
            'accounting.reports.balance-sheet' => 'Accounting/Reports/BalanceSheet',
            'accounting.reports.receivables' => 'Accounting/Reports/Receivables',
            'accounting.reports.payables' => 'Accounting/Reports/Payables',
            'accounting.reports.day-book' => 'Accounting/Reports/DayBook',
            'accounting.reports.cash-book' => 'Accounting/Reports/CashBook',
            'accounting.reports.voucher-register' => 'Accounting/Reports/VoucherRegister',
            'accounting.reports.expenses' => 'Accounting/Reports/Expenses',
            'accounting.reports.gst' => 'Accounting/Reports/Gst',
            'accounting.reports.tds' => 'Accounting/Reports/Tds',
        ];

        foreach ($pages as $route => $component) {
            $this->actingAs($context['user'])
                ->get(route($route))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }

        $this->actingAs($context['user'])
            ->get(route('accounting.reports.party-ledger', ['account_id' => $partyAccountId]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/Reports/PartyLedger')
                ->where('ledger.closing', 1160));

        $this->actingAs($context['user'])
            ->get(route('accounting.reports.voucher-register', ['voucher_type' => 'sales']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('rows', 1)
                ->where('rows.0.source.module', 'Invoice'));

        $this->actingAs($context['user'])
            ->get(route('accounting.reports.receivables'))
            ->assertInertia(fn (Assert $page) => $page->where('rows.0.outstanding', 1160));
    }

    /**
     * Invoice 1,000 + 180 GST; diesel 500 and general expense 250 paid in cash; 20 TDS deducted by the party.
     *
     * @param  array<string, mixed>  $context
     */
    private function seedTransactions(array $context): Party
    {
        $companyId = $context['company']->id;
        $party = Party::create([
            'company_id' => $companyId,
            'name' => 'Report Party',
            'address' => 'Road',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9000000004',
            'consider_for_accounting' => true,
        ]);
        $partyAccount = app(EntityAccountLinker::class)->enable($party);

        $invoice = Invoice::create([
            'company_id' => $companyId,
            'branch_id' => $context['branch']->id,
            'invoice_no' => 'INV-REP-1',
            'date' => now()->toDateString(),
            'party_id' => $party->id,
            'status' => 'Pending',
            'total_amount' => 1000,
            'gst_amount' => 180,
            'net_amount' => 1180,
        ]);
        app(OperationalAccountingBridge::class)->sync($invoice);

        $this->postExpense($companyId, $context['branch']->id, 'diesel_expense', 500);
        $this->postExpense($companyId, $context['branch']->id, 'general_expense', 250);

        app(VoucherService::class)->post([
            'company_id' => $companyId,
            'branch_id' => $context['branch']->id,
            'voucher_type' => AccountingVoucherType::Journal,
            'date' => now()->toDateString(),
            'event_key' => 'manual.tds_receivable',
        ], [
            ['account_id' => $this->systemAccountId($companyId, 'tds_receivable'), 'debit' => 20],
            ['account_id' => $partyAccount->id, 'credit' => 20],
        ]);

        return $party;
    }

    private function postExpense(int $companyId, int $branchId, string $expenseKey, float $amount): void
    {
        app(VoucherService::class)->post([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'voucher_type' => AccountingVoucherType::Payment,
            'date' => now()->toDateString(),
        ], [
            ['account_id' => $this->systemAccountId($companyId, $expenseKey), 'debit' => $amount],
            ['account_id' => $this->systemAccountId($companyId, 'cash'), 'credit' => $amount],
        ]);
    }
}
