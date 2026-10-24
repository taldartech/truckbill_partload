<?php

namespace Tests\Feature\E2E;

use App\Services\Accounting\EnsureCompanyAccounting;
use App\Support\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

/**
 * PHP end-to-end surface coverage: every primary portal page must render for an
 * operational admin with full permissions (mirrors the sidebar / portal map).
 */
class PortalSurfaceCoverageTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function portalPages(): array
    {
        return [
            'dashboard' => ['dashboard', 'Dashboard'],
            'quick-search' => ['quick-search.index', 'QuickSearch'],
            'bilties.index' => ['bilties.index', 'Bilties/Index'],
            'bilties.create' => ['bilties.create', 'Bilties/Create'],
            'loading-reports' => ['loading-reports.create', 'LoadingReports/Create'],
            'challans.index' => ['challans.index', 'Challans/Index'],
            'challans.create' => ['challans.create', 'Challans/Create'],
            'main-challans.index' => ['main-challans.index', 'MainChallans/Index'],
            'main-challans.create' => ['main-challans.create', 'MainChallans/Create'],
            'inward-registers.index' => ['inward-registers.index', 'InwardRegisters/Index'],
            'inward-registers.create' => ['inward-registers.create', 'InwardRegisters/Create'],
            'delivery-receipts.index' => ['delivery-receipts.index', 'DeliveryReceipts/Index'],
            'delivery-receipts.create' => ['delivery-receipts.create', 'DeliveryReceipts/Create'],
            'invoices.index' => ['invoices.index', 'Invoices/Index'],
            'invoices.create' => ['invoices.create', 'Invoices/Create'],
            'accounting.vouchers.index' => ['accounting.vouchers.index', 'Accounting/Vouchers/Index'],
            'accounting.vouchers.create' => ['accounting.vouchers.create', 'Accounting/Vouchers/Create'],
            'bank-accounts.index' => ['bank-accounts.index', 'BankAccounts/Index'],
            'accounting.accounts.index' => ['accounting.accounts.index', 'Accounting/Accounts/Index'],
            'accounting.account-groups.index' => ['accounting.account-groups.index', 'Accounting/AccountGroups/Index'],
            'accounting.opening-balances.create' => ['accounting.opening-balances.create', 'Accounting/OpeningBalances/Create'],
            'accounting.financial-years.index' => ['accounting.financial-years.index', 'Accounting/FinancialYears/Index'],
            'accounting.health' => ['accounting.health', 'Accounting/Health/Index'],
            'parties.index' => ['parties.index', 'Parties/Index'],
            'stations.index' => ['stations.index', 'Stations/Index'],
            'goods.index' => ['goods.index', 'Goods/Index'],
            'groups.index' => ['groups.index', 'Groups/Index'],
            'package-types.index' => ['package-types.index', 'PackageTypes/Index'],
            'trucks.index' => ['trucks.index', 'Trucks/Index'],
            'trucks.create' => ['trucks.create', 'Trucks/Create'],
            'truck-types.index' => ['truck-types.index', 'TruckTypes/Index'],
            'agents.index' => ['agents.index', 'Agents/Index'],
            'accounting.reports.ledger' => ['accounting.reports.ledger', 'Accounting/Reports/Ledger'],
            'accounting.reports.day-book' => ['accounting.reports.day-book', 'Accounting/Reports/DayBook'],
            'accounting.reports.cash-book' => ['accounting.reports.cash-book', 'Accounting/Reports/CashBook'],
            'accounting.reports.bank-book' => ['accounting.reports.bank-book', 'Accounting/Reports/BankBook'],
            'accounting.reports.trial-balance' => ['accounting.reports.trial-balance', 'Accounting/Reports/TrialBalance'],
            'accounting.reports.profit-and-loss' => ['accounting.reports.profit-and-loss', 'Accounting/Reports/ProfitAndLoss'],
            'accounting.reports.balance-sheet' => ['accounting.reports.balance-sheet', 'Accounting/Reports/BalanceSheet'],
            'accounting.reports.receivables' => ['accounting.reports.receivables', 'Accounting/Reports/Receivables'],
            'accounting.reports.payables' => ['accounting.reports.payables', 'Accounting/Reports/Payables'],
            'accounting.reports.party-ledger' => ['accounting.reports.party-ledger', 'Accounting/Reports/PartyLedger'],
            'accounting.reports.voucher-register' => ['accounting.reports.voucher-register', 'Accounting/Reports/VoucherRegister'],
            'accounting.reports.expenses' => ['accounting.reports.expenses', 'Accounting/Reports/Expenses'],
            'accounting.reports.gst' => ['accounting.reports.gst', 'Accounting/Reports/Gst'],
            'accounting.reports.tds' => ['accounting.reports.tds', 'Accounting/Reports/Tds'],
            'reports.bilty-register' => ['reports.bilty-register', 'Reports/BiltyRegister'],
            'reports.challan-register' => ['reports.challan-register', 'Reports/ChallanRegister'],
            'branches.index' => ['branches.index', 'Branches/Index'],
            'password.edit' => ['password.edit', 'Password/Edit'],
        ];
    }

    #[DataProvider('portalPages')]
    public function test_portal_page_renders_for_operational_admin(string $routeName, ?string $component): void
    {
        $context = $this->createTenantContext(RolePermissions::adminPermissions());
        app(EnsureCompanyAccounting::class)->handle($context['company']);

        $response = $this->actingAs($context['user'])->get(route($routeName));

        $response->assertOk();

        if ($component !== null) {
            $response->assertInertia(fn (AssertableInertia $page) => $page->component($component, false));
        }
    }

    public function test_guest_is_redirected_from_portal_surfaces_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('bilties.index'))->assertRedirect(route('login'));
        $this->get(route('parties.index'))->assertRedirect(route('login'));
        $this->get(route('accounting.accounts.index'))->assertRedirect(route('login'));
    }

    public function test_auth_login_logout_journey(): void
    {
        $context = $this->createTenantContext(RolePermissions::adminPermissions());

        $this->post('/login', [
            'email' => $context['user']->email,
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($context['user']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Dashboard'));

        $this->post(route('logout'))->assertRedirect('/login');
        $this->assertGuest();
    }
}
