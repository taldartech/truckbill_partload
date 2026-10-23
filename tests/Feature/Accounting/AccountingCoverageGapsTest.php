<?php

namespace Tests\Feature\Accounting;

use App\Enums\AccountingVoucherType;
use App\Models\AccountingVoucher;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\User;
use App\Services\Accounting\AccountingAuditTrail;
use App\Services\Accounting\AccountingIntegrity;
use App\Services\Accounting\EntityAccountLinker;
use App\Services\Accounting\Reports\ExpenseReport;
use App\Services\Accounting\Reports\GstReport;
use App\Services\Accounting\Reports\OutstandingReport;
use App\Services\Accounting\Reports\TrialBalanceReport;
use App\Services\Accounting\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class AccountingCoverageGapsTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    public function test_ledger_and_bank_book_pages_render_without_selected_account(): void
    {
        $context = $this->accountingContext(['view accounting_reports']);
        $cashId = $this->systemAccountId($context['company'], 'cash');

        $this->actingAs($context['user'])
            ->get(route('accounting.reports.ledger', ['account_id' => $cashId]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/Reports/Ledger')
                ->where('account.id', $cashId));

        $this->actingAs($context['user'])
            ->get(route('accounting.reports.bank-book'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/Reports/BankBook')
                ->where('ledger', null));
    }

    public function test_reports_handle_company_without_chart_of_accounts(): void
    {
        $owner = User::factory()->create();
        $company = Company::create(['name' => 'Bare Co', 'user_id' => $owner->id]);
        $today = now()->toDateString();

        $expense = app(ExpenseReport::class)->build($company->id, $today, $today);
        $this->assertSame(['months' => [], 'groups' => [], 'total' => 0.0], $expense);

        $gst = app(GstReport::class)->build($company->id, $today, $today);
        $this->assertSame([], $gst['rows']);
        $this->assertSame(0.0, $gst['totals']['net_payable']);
    }

    public function test_reports_skip_accounts_with_no_net_activity_and_report_input_gst(): void
    {
        $context = $this->accountingContext();
        $companyId = $context['company']->id;
        $branchId = $context['branch']->id;
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $debtor = app(EntityAccountLinker::class)->enable($this->party($context));

        $this->journal($companyId, $branchId, $debtor->id, $this->systemAccountId($companyId, 'cash'), 100);
        $this->journal($companyId, $branchId, $this->systemAccountId($companyId, 'cash'), $debtor->id, 100);

        $trial = app(TrialBalanceReport::class)->build($companyId, $tomorrow, $tomorrow);
        $this->assertSame([], $trial['rows']);

        $outstanding = app(OutstandingReport::class)->build($companyId, 'sundry_debtors', $tomorrow, $tomorrow);
        $this->assertSame([], $outstanding);

        $expenseAccountId = $this->systemAccountId($companyId, 'diesel_expense');
        $this->journal($companyId, $branchId, $expenseAccountId, $this->systemAccountId($companyId, 'cash'), 50);
        $this->journal($companyId, $branchId, $this->systemAccountId($companyId, 'cash'), $expenseAccountId, 50);

        $expense = app(ExpenseReport::class)->build($companyId, $today, $today);
        $this->assertSame([], $expense['groups']);

        $this->journal($companyId, $branchId, $this->systemAccountId($companyId, 'gst_input'), $this->systemAccountId($companyId, 'cash'), 18);

        $gst = app(GstReport::class)->build($companyId, $today, $today);
        $this->assertSame('input', $gst['rows'][0]['kind']);
        $this->assertSame(18.0, $gst['rows'][0]['amount']);
        $this->assertSame(-18.0, $gst['totals']['net_payable']);
    }

    public function test_deactivating_bank_account_without_ledger_returns_null(): void
    {
        $context = $this->accountingContext();
        $bank = BankAccount::create([
            'company_id' => $context['company']->id,
            'name' => 'Unlinked Bank',
            'is_active' => true,
        ]);

        $this->assertNull(app(EntityAccountLinker::class)->deactivateBankAccount($bank));
    }

    public function test_audit_trail_ignores_sources_that_belong_to_another_company(): void
    {
        $context = $this->accountingContext();
        $other = $this->accountingContext([], 'B');

        $foreignInvoice = $this->invoice($other, 'INV-FOREIGN');
        $ownVoucher = $this->postFor($context, $foreignInvoice->id);
        $foreignVoucher = $this->postFor($other, $foreignInvoice->id);

        $references = app(AccountingAuditTrail::class)->sourceReferences(collect([
            $ownVoucher->fresh(),
            $foreignVoucher->fresh(),
        ]));

        $this->assertTrue($references[$ownVoucher->id]['deleted']);
        $this->assertSame('#'.$foreignInvoice->id, $references[$ownVoucher->id]['number']);
        $this->assertFalse($references[$foreignVoucher->id]['deleted']);
        $this->assertSame('INV-FOREIGN', $references[$foreignVoucher->id]['number']);
    }

    public function test_audit_trail_and_integrity_tolerate_unknown_and_missing_sources(): void
    {
        $context = $this->accountingContext();
        $companyId = $context['company']->id;

        $unknownTypeVoucher = $this->postFor($context, 1);
        AccountingVoucher::query()->withoutGlobalScopes()->whereKey($unknownTypeVoucher->id)->update([
            'source_type' => 'App\\Models\\DoesNotExist',
            'source_id' => 7,
        ]);
        $missingSourceVoucher = $this->postFor($context, 999999);

        $references = app(AccountingAuditTrail::class)->sourceReferences(
            AccountingVoucher::query()->withoutGlobalScopes()->whereIn('id', [$unknownTypeVoucher->id, $missingSourceVoucher->id])->get()
        );
        $this->assertTrue($references[$unknownTypeVoucher->id]['deleted']);
        $this->assertSame('DoesNotExist', $references[$unknownTypeVoucher->id]['module']);

        $checks = app(AccountingIntegrity::class)->audit($companyId)['checks'];

        $this->assertSame(0, $checks['tenant_mismatch']['count']);
        $this->assertSame(2, $checks['orphans']['count']);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function party(array $context): Party
    {
        return Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Gap Party '.uniqid(),
            'address' => 'Road',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9000000007',
            'consider_for_accounting' => true,
        ]);
    }

    private function journal(int $companyId, int $branchId, int $debitAccountId, int $creditAccountId, float $amount): AccountingVoucher
    {
        return app(VoucherService::class)->post([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'voucher_type' => AccountingVoucherType::Journal,
            'date' => now()->toDateString(),
        ], [
            ['account_id' => $debitAccountId, 'debit' => $amount, 'credit' => 0],
            ['account_id' => $creditAccountId, 'debit' => 0, 'credit' => $amount],
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function invoice(array $context, string $number): Invoice
    {
        return Invoice::create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => $number,
            'date' => now()->toDateString(),
            'party_id' => app(EntityAccountLinker::class)->enable($this->party($context))->reference_id,
            'status' => 'Pending',
            'total_amount' => 1000,
            'net_amount' => 1000,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function postFor(array $context, int $invoiceId): AccountingVoucher
    {
        $companyId = $context['company']->id;

        return app(VoucherService::class)->post([
            'company_id' => $companyId,
            'branch_id' => $context['branch']->id,
            'voucher_type' => AccountingVoucherType::Sales,
            'date' => now()->toDateString(),
            'source_type' => Invoice::class,
            'source_id' => $invoiceId,
            'event_key' => 'invoice.freight',
        ], [
            ['account_id' => $this->systemAccountId($companyId, 'sundry_debtors_unlinked'), 'debit' => 1000],
            ['account_id' => $this->systemAccountId($companyId, 'freight_income'), 'credit' => 1000],
        ]);
    }
}
