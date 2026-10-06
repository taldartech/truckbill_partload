<?php

namespace Tests\Feature\Accounting;

use App\Enums\AccountingVoucherType;
use App\Models\AccountingSourceLink;
use App\Models\AccountingVoucher;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Voucher;
use App\Services\Accounting\AccountingIntegrity;
use App\Services\Accounting\AccountingReconciler;
use App\Services\Accounting\EntityAccountLinker;
use App\Services\Accounting\OperationalAccountingBridge;
use App\Services\Accounting\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class AccountingIntegrityTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    public function test_clean_company_has_no_issues(): void
    {
        $context = $this->accountingContext();
        $invoice = $this->invoice($context, 'INV-OK');
        app(OperationalAccountingBridge::class)->sync($invoice);

        $this->assertSame(0, app(AccountingIntegrity::class)->audit($context['company']->id)['total']);
    }

    public function test_audit_detects_missing_stale_and_orphan_postings(): void
    {
        $context = $this->accountingContext();
        $this->invoice($context, 'INV-MISSING');

        $stale = $this->invoice($context, 'INV-STALE');
        app(OperationalAccountingBridge::class)->sync($stale);
        Invoice::query()->whereKey($stale->id)->update(['net_amount' => 2000, 'total_amount' => 2000]);

        $orphan = $this->invoice($context, 'INV-ORPHAN');
        app(OperationalAccountingBridge::class)->sync($orphan);
        Invoice::query()->whereKey($orphan->id)->delete();

        $checks = app(AccountingIntegrity::class)->audit($context['company']->id)['checks'];

        $this->assertSame(1, $checks['missing']['count']);
        $this->assertSame('INV-MISSING', $checks['missing']['items'][0]['number']);
        $this->assertSame(1, $checks['stale']['count']);
        $this->assertSame('INV-STALE', $checks['stale']['items'][0]['number']);
        $this->assertSame(1, $checks['orphans']['count']);
        $this->assertTrue($checks['orphans']['items'][0]['deleted']);
    }

    public function test_audit_detects_duplicates_unbalanced_mismatch_fy_and_cross_company_accounts(): void
    {
        $context = $this->accountingContext();
        $other = $this->accountingContext([], 'B');
        $companyId = $context['company']->id;
        $invoice = $this->invoice($context, 'INV-DUP');

        $first = $this->postFor($invoice, $companyId, $context['branch']->id);
        $this->postFor($invoice, $companyId, $context['branch']->id);
        $this->assertNotNull($first);

        $unbalanced = $this->postFor(null, $companyId, $context['branch']->id);
        DB::table('accounting_voucher_entries')->where('voucher_id', $unbalanced->id)->where('debit', '>', 0)->update(['debit' => 99]);

        AccountingVoucher::query()->withoutGlobalScopes()->whereKey($first->id)->update(['branch_id' => $other['branch']->id]);

        $foreignYear = FinancialYear::query()->withoutGlobalScopes()->where('company_id', $other['company']->id)->firstOrFail();
        AccountingVoucher::query()->withoutGlobalScopes()->whereKey($unbalanced->id)->update(['financial_year_id' => $foreignYear->id]);

        DB::table('accounting_voucher_entries')->where('voucher_id', $unbalanced->id)->where('credit', '>', 0)
            ->update(['account_id' => $this->systemAccountId($other['company'], 'cash')]);

        $checks = app(AccountingIntegrity::class)->audit($companyId)['checks'];

        $this->assertSame(1, $checks['duplicates']['count']);
        $this->assertCount(2, $checks['duplicates']['items'][0]['voucher_numbers']);
        $this->assertSame(1, $checks['unbalanced']['count']);
        $this->assertSame(1, $checks['tenant_mismatch']['count']);
        $this->assertSame('Branch differs', $checks['tenant_mismatch']['items'][0]['detail']);
        $this->assertSame(1, $checks['outside_financial_year']['count']);
        $this->assertSame(1, $checks['cross_company_accounts']['count']);
    }

    public function test_reconcile_dry_run_changes_nothing_and_commit_fixes_issues(): void
    {
        $context = $this->accountingContext();
        $companyId = $context['company']->id;
        $missing = $this->invoice($context, 'INV-REC-1');

        $legacy = $this->invoice($context, 'INV-REC-2');
        $legacyVoucher = $this->postFor($legacy, $companyId, $context['branch']->id, eventKey: null);

        $reconciler = app(AccountingReconciler::class);
        $preview = $reconciler->run($companyId);

        $this->assertSame(1, $preview['post']);
        $this->assertSame(1, $preview['links']);
        $this->assertSame(1, AccountingVoucher::query()->withoutGlobalScopes()->count());
        $this->assertSame(0, AccountingSourceLink::query()->withoutGlobalScopes()->count());

        $result = $reconciler->run($companyId, dryRun: false);

        $this->assertSame(0, $result['errors']);
        $this->assertCount(1, app(OperationalAccountingBridge::class)->activeVouchers($missing));
        $link = AccountingSourceLink::query()->withoutGlobalScopes()->where('source_id', $legacy->id)->sole();
        $this->assertSame('invoice.freight', $link->event_key);
        $this->assertSame('invoice.freight', $legacyVoucher->fresh()->event_key);

        $again = $reconciler->run($companyId);
        $this->assertSame(0, $again['post'] + $again['repost'] + $again['reverse'] + $again['links'] + $again['orphans']);
    }

    public function test_reconcile_event_filter_only_touches_that_event(): void
    {
        $context = $this->accountingContext();
        $invoice = $this->invoice($context, 'INV-EVT');

        $summary = app(AccountingReconciler::class)->run($context['company']->id, 'inward.agent_crossing', false);

        $this->assertSame(0, $summary['documents']);
        $this->assertCount(0, app(OperationalAccountingBridge::class)->activeVouchers($invoice));

        app(AccountingReconciler::class)->run($context['company']->id, 'invoice.freight', false);
        $this->assertCount(1, app(OperationalAccountingBridge::class)->activeVouchers($invoice));
    }

    public function test_reconcile_migrates_legacy_payment_and_receipt_vouchers(): void
    {
        $context = $this->accountingContext();
        $companyId = $context['company']->id;
        $party = $this->party($context, true);

        $receipt = Voucher::create([
            'company_id' => $companyId,
            'branch_id' => $context['branch']->id,
            'voucher_number' => 'OLD-R1',
            'date' => now()->toDateString(),
            'voucher_type' => 'Receipt',
            'amount' => 750,
            'mode' => 'cash',
            'accountable_type' => Party::class,
            'accountable_id' => $party->id,
        ]);
        $payment = Voucher::create([
            'company_id' => $companyId,
            'branch_id' => $context['branch']->id,
            'voucher_number' => 'OLD-P1',
            'date' => now()->toDateString(),
            'voucher_type' => 'Payment',
            'amount' => 120,
            'mode' => 'cash',
        ]);
        $deleted = Voucher::create([
            'company_id' => $companyId,
            'voucher_number' => 'OLD-D1',
            'date' => now()->toDateString(),
            'voucher_type' => 'Receipt',
            'amount' => 50,
            'mode' => 'cash',
        ]);
        $deleted->delete();

        $summary = app(AccountingReconciler::class)->run($companyId, 'legacy_voucher.payment_receipt', false);
        $this->assertSame(2, $summary['post']);

        $bridge = app(OperationalAccountingBridge::class);
        $receiptVoucher = $bridge->activeVouchers($receipt)->sole();
        $this->assertSame(AccountingVoucherType::Receipt, $receiptVoucher->voucher_type);
        $partyLine = $receiptVoucher->entries->firstWhere('account_id', app(EntityAccountLinker::class)->linkedAccount($party)->id);
        $this->assertEquals(750, (float) $partyLine->credit);

        $paymentVoucher = $bridge->activeVouchers($payment)->sole();
        $controlLine = $paymentVoucher->entries->firstWhere('account_id', $this->systemAccountId($companyId, 'sundry_creditors_unlinked'));
        $this->assertEquals(120, (float) $controlLine->debit);

        $this->assertCount(0, $bridge->activeVouchers($deleted));
    }

    public function test_audit_and_reconcile_commands_run(): void
    {
        $context = $this->accountingContext();
        $this->invoice($context, 'INV-CMD');

        $this->artisan('accounting:audit', ['--company' => $context['company']->id])
            ->expectsOutputToContain('1 issue(s)')
            ->assertFailed();

        $this->artisan('accounting:reconcile', ['--company' => $context['company']->id])
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();
        $this->assertSame(0, AccountingVoucher::query()->withoutGlobalScopes()->count());

        $this->artisan('accounting:reconcile', ['--company' => $context['company']->id, '--commit' => true])->assertSuccessful();
        $this->assertSame(1, AccountingVoucher::query()->withoutGlobalScopes()->count());

        $this->artisan('accounting:audit', ['--company' => $context['company']->id])->assertSuccessful();
        $this->artisan('accounting:reconcile', ['--event' => 'nope'])->assertExitCode(2);
    }

    public function test_health_page_lists_checks(): void
    {
        $context = $this->accountingContext(['view accounting_settings']);
        $this->invoice($context, 'INV-HEALTH');

        $this->actingAs($context['user'])
            ->get(route('accounting.health'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/Health/Index')
                ->where('report.total', 1)
                ->where('report.checks.missing.items.0.number', 'INV-HEALTH'));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function party(array $context, bool $accounting): Party
    {
        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Integrity Party '.uniqid(),
            'address' => 'Road',
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
            'mobile' => '9000000005',
            'consider_for_accounting' => $accounting,
        ]);

        if ($accounting) {
            app(EntityAccountLinker::class)->enable($party);
        }

        return $party;
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
            'party_id' => $this->party($context, true)->id,
            'status' => 'Pending',
            'total_amount' => 1000,
            'net_amount' => 1000,
        ]);
    }

    private function postFor(?Invoice $invoice, int $companyId, int $branchId, ?string $eventKey = 'invoice.freight'): AccountingVoucher
    {
        return app(VoucherService::class)->post([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'voucher_type' => AccountingVoucherType::Sales,
            'date' => now()->toDateString(),
            'narration' => $invoice ? 'Invoice '.$invoice->invoice_no : 'Manual',
            'source_type' => $invoice ? Invoice::class : null,
            'source_id' => $invoice?->id,
            'event_key' => $invoice ? $eventKey : null,
        ], [
            ['account_id' => $this->systemAccountId($companyId, 'sundry_debtors_unlinked'), 'debit' => 1000],
            ['account_id' => $this->systemAccountId($companyId, 'freight_income'), 'credit' => 1000],
        ]);
    }
}
