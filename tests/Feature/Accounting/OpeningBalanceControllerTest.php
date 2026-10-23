<?php

namespace Tests\Feature\Accounting;

use App\Enums\AccountingVoucherType;
use App\Models\AccountingVoucher;
use App\Services\Accounting\FinancialYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class OpeningBalanceControllerTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    private const ALL = ['view accounting_settings', 'create accounting_settings'];

    public function test_create_renders_with_financial_year_and_selectable_accounts(): void
    {
        $context = $this->accountingContext(self::ALL);

        $this->actingAs($context['user'])
            ->get(route('accounting.opening-balances.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Accounting/OpeningBalances/Create')
                ->where('financialYear.is_current', true)
                ->where('financialYear.company_id', $context['company']->id)
                ->has('accounts')
                ->has('accounts.0.label'));
    }

    public function test_create_requires_permission(): void
    {
        $context = $this->accountingContext(['create accounting_settings']);

        $this->actingAs($context['user'])
            ->get(route('accounting.opening-balances.create'))
            ->assertForbidden();
    }

    public function test_store_posts_opening_voucher_and_redirects_to_it(): void
    {
        $context = $this->accountingContext(self::ALL);

        $response = $this->actingAs($context['user'])
            ->post(route('accounting.opening-balances.store'), [
                'date' => now()->toDateString(),
                'entries' => [
                    ['account_id' => $this->systemAccountId($context['company'], 'cash'), 'debit' => 5000],
                    ['account_id' => $this->systemAccountId($context['company'], 'owners_capital'), 'credit' => 5000],
                ],
            ]);

        $voucher = AccountingVoucher::query()->sole();
        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('accounting.vouchers.show', $voucher))
            ->assertSessionHas('success', 'Opening balance voucher posted.');

        $this->assertSame(AccountingVoucherType::Opening, $voucher->voucher_type);
        $this->assertSame('Opening balances', $voucher->narration);
        $this->assertSame($context['company']->id, $voucher->company_id);
        $this->assertSame($context['branch']->id, $voucher->branch_id);
        $this->assertEquals(5000.0, $voucher->totalDebit());
        $this->assertEquals(5000.0, $voucher->totalCredit());
    }

    public function test_store_uses_supplied_narration(): void
    {
        $context = $this->accountingContext(self::ALL);

        $this->actingAs($context['user'])
            ->post(route('accounting.opening-balances.store'), [
                'date' => now()->toDateString(),
                'narration' => 'FY opening',
                'entries' => [
                    ['account_id' => $this->systemAccountId($context['company'], 'cash'), 'debit' => 10],
                    ['account_id' => $this->systemAccountId($context['company'], 'owners_capital'), 'credit' => 10],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('FY opening', AccountingVoucher::query()->sole()->narration);
    }

    public function test_store_validates_payload(): void
    {
        $context = $this->accountingContext(self::ALL);

        $this->actingAs($context['user'])
            ->post(route('accounting.opening-balances.store'), [])
            ->assertSessionHasErrors(['date', 'entries']);

        $this->actingAs($context['user'])
            ->post(route('accounting.opening-balances.store'), [
                'date' => now()->toDateString(),
                'entries' => [['account_id' => $this->systemAccountId($context['company'], 'cash'), 'debit' => 10]],
            ])
            ->assertSessionHasErrors('entries');

        $this->actingAs($context['user'])
            ->post(route('accounting.opening-balances.store'), [
                'date' => now()->toDateString(),
                'entries' => [
                    ['account_id' => 999999, 'debit' => -5],
                    ['account_id' => 999998, 'credit' => 'abc'],
                ],
            ])
            ->assertSessionHasErrors(['entries.0.account_id', 'entries.0.debit', 'entries.1.account_id', 'entries.1.credit']);

        $this->assertSame(0, AccountingVoucher::query()->count());
    }

    public function test_store_rejects_accounts_from_another_company(): void
    {
        $context = $this->accountingContext(self::ALL);
        $other = $this->accountingContext([], 'B');

        $this->actingAs($context['user'])
            ->post(route('accounting.opening-balances.store'), [
                'date' => now()->toDateString(),
                'entries' => [
                    ['account_id' => $this->systemAccountId($other['company'], 'cash'), 'debit' => 10],
                    ['account_id' => $this->systemAccountId($context['company'], 'owners_capital'), 'credit' => 10],
                ],
            ])
            ->assertSessionHasErrors('entries.0.account_id');

        $this->assertSame(0, AccountingVoucher::query()->count());
    }

    public function test_store_rejects_unbalanced_entries(): void
    {
        $context = $this->accountingContext(self::ALL);

        $this->actingAs($context['user'])
            ->post(route('accounting.opening-balances.store'), [
                'date' => now()->toDateString(),
                'entries' => [
                    ['account_id' => $this->systemAccountId($context['company'], 'cash'), 'debit' => 100],
                    ['account_id' => $this->systemAccountId($context['company'], 'owners_capital'), 'credit' => 90],
                ],
            ])
            ->assertSessionHasErrors('entries');

        $this->assertSame(0, AccountingVoucher::query()->count());
    }

    public function test_store_is_blocked_when_financial_year_is_closed(): void
    {
        $context = $this->accountingContext(self::ALL);
        $years = app(FinancialYearService::class);
        $years->close($years->ensureCurrent($context['company']), $context['user']->id);

        $this->actingAs($context['user'])
            ->post(route('accounting.opening-balances.store'), [
                'date' => now()->toDateString(),
                'entries' => [
                    ['account_id' => $this->systemAccountId($context['company'], 'cash'), 'debit' => 10],
                    ['account_id' => $this->systemAccountId($context['company'], 'owners_capital'), 'credit' => 10],
                ],
            ])
            ->assertSessionHasErrors('financial_year_id');

        $this->assertSame(0, AccountingVoucher::query()->count());
    }

    public function test_store_requires_permission(): void
    {
        $context = $this->accountingContext(['view accounting_settings']);

        $this->actingAs($context['user'])
            ->post(route('accounting.opening-balances.store'), [
                'date' => now()->toDateString(),
                'entries' => [
                    ['account_id' => $this->systemAccountId($context['company'], 'cash'), 'debit' => 10],
                    ['account_id' => $this->systemAccountId($context['company'], 'owners_capital'), 'credit' => 10],
                ],
            ])
            ->assertForbidden();

        $this->assertSame(0, AccountingVoucher::query()->count());
    }
}
