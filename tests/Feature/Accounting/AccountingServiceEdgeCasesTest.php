<?php

namespace Tests\Feature\Accounting;

use App\Enums\AccountingVoucherType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Agent;
use App\Models\Bilty;
use App\Models\Company;
use App\Models\DeliveryReceipt;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Station;
use App\Models\Voucher;
use App\Services\Accounting\AccountingIntegrity;
use App\Services\Accounting\AccountingMapper;
use App\Services\Accounting\AccountingReconciler;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Accounting\EntityAccountLinker;
use App\Services\Accounting\FinancialYearService;
use App\Services\Accounting\OperationalAccountingBridge;
use App\Services\Accounting\Reports\BalanceSheetReport;
use App\Services\Accounting\Reports\OutstandingReport;
use App\Services\Accounting\Reports\ProfitAndLossReport;
use App\Services\Accounting\Reports\TdsReport;
use App\Services\Accounting\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use ReflectionClass;
use RuntimeException;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class AccountingServiceEdgeCasesTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    /**
     * @param  callable(): mixed  $callback
     * @return array<string, list<string>>
     */
    private function validationErrors(callable $callback): array
    {
        try {
            $callback();
        } catch (ValidationException $e) {
            return $e->errors();
        }

        $this->fail('Expected a ValidationException.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<array<string, mixed>>}
     */
    private function journal(int $companyId, int $debitAccount, int $creditAccount, array $extra = []): array
    {
        return [
            array_merge([
                'company_id' => $companyId,
                'voucher_type' => AccountingVoucherType::Journal,
                'date' => now()->toDateString(),
            ], $extra),
            [
                ['account_id' => $debitAccount, 'debit' => 100, 'credit' => 0],
                ['account_id' => $creditAccount, 'debit' => 0, 'credit' => 100],
            ],
        ];
    }

    public function test_voucher_service_validation_failures(): void
    {
        $context = $this->accountingContext();
        $other = $this->accountingContext([], 'B');
        $company = $context['company'];
        $service = app(VoucherService::class);
        $cash = $this->systemAccountId($company, 'cash');
        $capital = $this->systemAccountId($company, 'owners_capital');

        $foreignYear = app(FinancialYearService::class)->ensureCurrent($other['company']);
        $errors = $this->validationErrors(fn () => $service->post(...$this->journal($company->id, $cash, $capital, ['financial_year_id' => $foreignYear->id])));
        $this->assertArrayHasKey('financial_year_id', $errors);

        $year = app(FinancialYearService::class)->ensureCurrent($company);
        $errors = $this->validationErrors(fn () => $service->post(...$this->journal($company->id, $cash, $capital, [
            'financial_year_id' => $year->id,
            'date' => now()->subYears(5)->toDateString(),
        ])));
        $this->assertArrayHasKey('date', $errors);

        [$attributes] = $this->journal($company->id, $cash, $capital);
        $this->assertArrayHasKey('entries', $this->validationErrors(
            fn () => $service->post($attributes, [['account_id' => $cash, 'debit' => 1, 'credit' => 0]])
        ));
        $this->assertArrayHasKey('entries.0.account_id', $this->validationErrors(
            fn () => $service->post($attributes, [
                ['account_id' => 0, 'debit' => 1, 'credit' => 0],
                ['account_id' => $capital, 'debit' => 0, 'credit' => 1],
            ])
        ));
        $this->assertArrayHasKey('entries.0', $this->validationErrors(
            fn () => $service->post($attributes, [
                ['account_id' => $cash, 'debit' => 1, 'credit' => 1],
                ['account_id' => $capital, 'debit' => 0, 'credit' => 1],
            ])
        ));

        Account::query()->withoutGlobalScopes()->whereKey($capital)->update(['is_active' => false]);
        $errors = $this->validationErrors(fn () => $service->post(...$this->journal($company->id, $cash, $capital)));
        $this->assertSame(['Account is inactive.'], $errors['entries.1.account_id']);
    }

    public function test_cancel_and_reverse_guard_against_invalid_states_and_source_reversal(): void
    {
        $context = $this->accountingContext();
        $company = $context['company'];
        $service = app(VoucherService::class);
        $cash = $this->systemAccountId($company, 'cash');
        $capital = $this->systemAccountId($company, 'owners_capital');
        $party = Party::create(['company_id' => $company->id, 'name' => 'Source Party']);

        $voucher = $service->post(...$this->journal($company->id, $cash, $capital));
        $service->cancel($voucher, null, 'wrong entry');
        $this->assertArrayHasKey('voucher', $this->validationErrors(fn () => $service->cancel($voucher)));
        $this->assertArrayHasKey('voucher', $this->validationErrors(fn () => $service->reverse($voucher)));

        $first = $service->post(...$this->journal($company->id, $cash, $capital, [
            'source_type' => Party::class,
            'source_id' => $party->id,
            'event_key' => 'event.a',
        ]));
        $second = $service->post(...$this->journal($company->id, $cash, $capital, [
            'source_type' => Party::class,
            'source_id' => $party->id,
            'event_key' => 'event.b',
        ]));

        $service->reverseSourceVouchers($party, 'event.a');
        $this->assertNotNull($first->fresh()->reversed_at);
        $this->assertNull($second->fresh()->reversed_at);

        $service->reverseSourceVouchers($party);
        $this->assertNotNull($second->fresh()->reversed_at);
    }

    public function test_entity_account_linker_guards(): void
    {
        $context = $this->accountingContext();
        $linker = app(EntityAccountLinker::class);
        $station = Station::create(['name' => 'Unlinkable', 'owner_id' => $context['owner']->id]);

        $this->assertArrayHasKey('entity', $this->validationErrors(fn () => $linker->enable($station)));

        $party = Party::create(['company_id' => $context['company']->id, 'name' => 'Never Linked']);
        $this->assertNull($linker->disable($party));
        $this->assertNull($linker->bankLedger(null, $context['company']->id));

        $resolveGroup = (new ReflectionClass($linker))->getMethod('resolveGroup');
        $station->company_id = $context['company']->id;
        try {
            $resolveGroup->invoke($linker, $station, null, null);
            $this->fail('Expected group validation error.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('account_group_id', $e->errors());
        }
    }

    public function test_chart_of_accounts_repairs_missing_rows_and_parent_links(): void
    {
        $context = $this->accountingContext();
        $companyId = $context['company']->id;
        $chart = app(ChartOfAccountsService::class);

        $child = AccountGroup::query()->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNotNull('parent_id')
            ->firstOrFail();
        $child->forceFill(['parent_id' => null])->save();
        $chart->forgetEnsured($companyId);
        $chart->ensureDefaults($companyId);
        $this->assertNotNull($child->fresh()->parent_id);

        DB::table('accounts')->where('company_id', $companyId)->where('system_key', 'cash')->delete();
        $this->assertSame('cash', $chart->systemAccount($companyId, 'cash')->system_key);

        $leaf = AccountGroup::query()->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_system', true)
            ->whereNotIn('id', Account::query()->withoutGlobalScopes()->select('account_group_id'))
            ->whereNotIn('id', AccountGroup::query()->withoutGlobalScopes()->whereNotNull('parent_id')->select('parent_id'))
            ->first();
        if ($leaf !== null) {
            $key = $leaf->system_key;
            DB::table('account_groups')->where('id', $leaf->id)->delete();
            $this->assertSame($key, $chart->systemGroup($companyId, $key)->system_key);
        }

        $chart->forgetEnsured();
        $this->assertSame($companyId, $chart->systemAccount($companyId, 'cash')->company_id);
    }

    public function test_mapper_returns_null_when_documents_have_nothing_to_post(): void
    {
        $context = $this->accountingContext();
        $companyId = $context['company']->id;
        $mapper = app(AccountingMapper::class);

        $this->assertNull($mapper->map('invoice.freight', new Invoice(['company_id' => $companyId, 'net_amount' => 0])));
        $this->assertNull($mapper->map('invoice.freight', new Bilty(['company_id' => $companyId])));
        $this->assertNull($mapper->map('unknown.event', new Invoice(['company_id' => $companyId])));

        $this->assertNull($mapper->map('bilty.paid_receipt', new Bilty([
            'company_id' => $companyId, 'payment_type' => 'Paid', 'payment_mode' => 'Cash', 'freight' => 0,
        ])));
        $this->assertNull($mapper->map('bilty.advance_receipt', new Bilty([
            'company_id' => $companyId, 'less_advance_received' => 100, 'advance_payment_mode' => 'Bank',
        ])));
        $this->assertNull($mapper->map('bilty.cartage_due', new Bilty([
            'company_id' => $companyId, 'cartage' => 50, 'cartage_type' => 'Due', 'cartage_account_id' => 987654,
        ])));

        $this->assertNull($mapper->map('delivery_receipt.collection', new DeliveryReceipt([
            'company_id' => $companyId, 'mode_of_payment' => 'Cheque',
        ])));
        $this->assertNull($mapper->map('delivery_receipt.collection', new DeliveryReceipt([
            'company_id' => $companyId, 'mode_of_payment' => 'Cash', 'freight' => 0,
        ])));

        $this->assertNull($mapper->map('legacy_voucher.payment_receipt', new Voucher([
            'company_id' => $companyId, 'voucher_type' => 'Other', 'amount' => 10,
        ])));
        $this->assertNull($mapper->map('legacy_voucher.payment_receipt', new Voucher([
            'company_id' => $companyId, 'voucher_type' => 'Payment', 'amount' => 10, 'mode' => 'Bank',
        ])));
    }

    public function test_mapper_posts_legacy_agent_voucher_and_absorbs_small_round_off(): void
    {
        $context = $this->accountingContext();
        $companyId = $context['company']->id;
        $mapper = app(AccountingMapper::class);
        $agent = Agent::create(['company_id' => $companyId, 'name' => 'Legacy Agent']);

        $mapped = $mapper->map('legacy_voucher.payment_receipt', new Voucher([
            'company_id' => $companyId,
            'voucher_type' => 'Payment',
            'amount' => 250,
            'mode' => 'Cash',
            'accountable_type' => Agent::class,
            'accountable_id' => $agent->id,
        ]));

        $this->assertNotNull($mapped);
        $this->assertSame(AccountingVoucherType::Payment, $mapped['voucher_type']);
        $this->assertCount(2, $mapped['lines']);

        $finalize = (new ReflectionClass($mapper))->getMethod('finalize');
        $lines = [
            ['account_id' => $this->systemAccountId($companyId, 'cash'), 'debit' => 10050, 'credit' => 0, 'narration' => null],
            ['account_id' => $this->systemAccountId($companyId, 'freight_income'), 'debit' => 0, 'credit' => 10000, 'narration' => null],
        ];

        $rounded = $finalize->invoke($mapper, AccountingVoucherType::Journal, 'Rounding', $companyId, $lines);
        $this->assertCount(3, $rounded['lines']);
        $this->assertSame('0.50', $rounded['lines'][2]['credit']);

        $lines[0]['debit'] = 10500;
        $this->assertNull($finalize->invoke($mapper, AccountingVoucherType::Journal, 'Too far', $companyId, $lines));

        $lines[0]['debit'] = 9950;
        $negative = $finalize->invoke($mapper, AccountingVoucherType::Journal, 'Other side', $companyId, $lines);
        $this->assertSame('0.50', $negative['lines'][2]['debit']);
    }

    public function test_balance_sheet_includes_retained_earnings_and_skips_zero_balances(): void
    {
        $context = $this->accountingContext();
        $companyId = $context['company']->id;
        $service = app(VoucherService::class);
        $cash = $this->systemAccountId($companyId, 'cash');
        $income = $this->systemAccountId($companyId, 'freight_income');
        $capital = $this->systemAccountId($companyId, 'owners_capital');

        $service->post([
            'company_id' => $companyId,
            'voucher_type' => AccountingVoucherType::Journal,
            'date' => '2025-08-10',
        ], [
            ['account_id' => $cash, 'debit' => 500, 'credit' => 0],
            ['account_id' => $income, 'debit' => 0, 'credit' => 500],
        ]);

        $zero = $service->post([
            'company_id' => $companyId,
            'voucher_type' => AccountingVoucherType::Journal,
            'date' => '2026-05-10',
        ], [
            ['account_id' => $cash, 'debit' => 70, 'credit' => 0],
            ['account_id' => $capital, 'debit' => 0, 'credit' => 70],
        ]);
        $service->reverse($zero);

        foreach (['2026-09-30', '2027-02-15'] as $asOn) {
            $sheet = app(BalanceSheetReport::class)->build($companyId, $asOn, app(ProfitAndLossReport::class));

            $names = array_column($sheet['capital'], 'account_name');
            $this->assertContains('Profit & Loss (previous years)', $names);
            $this->assertNotContains('Owner\'s Capital', $names);
            $this->assertEquals($sheet['totals']['assets'], $sheet['totals']['liabilities_capital']);
        }
    }

    public function test_tds_and_outstanding_reports_handle_payables_and_missing_ledgers(): void
    {
        $context = $this->accountingContext();
        $companyId = $context['company']->id;
        $cash = $this->systemAccountId($companyId, 'cash');
        $tdsPayable = $this->systemAccountId($companyId, 'tds_payable');

        app(VoucherService::class)->post([
            'company_id' => $companyId,
            'voucher_type' => AccountingVoucherType::Journal,
            'date' => now()->toDateString(),
        ], [
            ['account_id' => $cash, 'debit' => 50, 'credit' => 0],
            ['account_id' => $tdsPayable, 'debit' => 0, 'credit' => 50],
        ]);

        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        $tds = app(TdsReport::class)->build($companyId, $from, $to);
        $this->assertEquals(50, $tds['totals']['payable']);
        $this->assertSame('Unallocated', $tds['rows'][0]['account_name']);

        $outstanding = app(OutstandingReport::class);
        $this->assertSame([], $outstanding->build($companyId, 'bank_accounts', $from, $to));
        $this->assertSame([], $outstanding->build($companyId, 'no_such_group', $from, $to));

        $emptyCompany = Company::create(['name' => 'Bare Co', 'user_id' => $context['owner']->id]);
        $this->assertSame(
            ['rows' => [], 'totals' => ['receivable' => 0.0, 'payable' => 0.0]],
            app(TdsReport::class)->build($emptyCompany->id, $from, $to)
        );
    }

    public function test_bridge_ignores_unsupported_documents_and_respects_only_events(): void
    {
        $context = $this->accountingContext();
        $bridge = app(OperationalAccountingBridge::class);
        $station = Station::create(['name' => 'Unsupported', 'owner_id' => $context['owner']->id]);

        $this->assertFalse($bridge->supports($station));
        $this->assertSame(0, $bridge->sync($station));
        $this->assertSame(['events' => [], 'orphans' => 0, 'unlinked' => 0], $bridge->preview($station));

        $invoice = new Invoice(['company_id' => $context['company']->id, 'net_amount' => 0]);
        $this->assertSame(0, $bridge->sync($invoice, ['invoice.freight']));

        $bilty = new Bilty(['company_id' => $context['company']->id]);
        $this->assertSame(0, $bridge->sync($bilty, ['bilty.paid_receipt']));
    }

    public function test_reconciler_reports_per_document_errors_and_continues(): void
    {
        $context = $this->accountingContext();
        $invoice = new Invoice(['company_id' => $context['company']->id]);

        $integrity = Mockery::mock(AccountingIntegrity::class);
        $integrity->shouldReceive('documents')->andReturn((function () use ($invoice) {
            yield $invoice;
        })());

        $bridge = Mockery::mock(OperationalAccountingBridge::class);
        $bridge->shouldReceive('preview')->andThrow(new RuntimeException('preview exploded'));

        $messages = [];
        $summary = (new AccountingReconciler($integrity, $bridge))->run(
            $context['company']->id,
            null,
            true,
            function (string $message) use (&$messages): void {
                $messages[] = $message;
            }
        );

        $this->assertSame(1, $summary['documents']);
        $this->assertSame(1, $summary['errors']);
        $this->assertStringContainsString('preview exploded', $messages[0]);
        $this->assertStringStartsWith('Invoice #', $messages[0]);
    }
}
