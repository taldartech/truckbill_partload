<?php

use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\AccountGroupController;
use App\Http\Controllers\Accounting\AccountingReportController;
use App\Http\Controllers\Accounting\AccountingVoucherController;
use App\Http\Controllers\Accounting\AccountOptionsController;
use App\Http\Controllers\Accounting\FinancialYearController;
use App\Http\Controllers\Accounting\OpeningBalanceController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\Api\OnboardingController as ApiOnboardingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BiltyController;
use App\Http\Controllers\BranchContextController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchSettingsController;
use App\Http\Controllers\ChallanController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryReceiptController;
use App\Http\Controllers\GoodController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InwardRegisterController;
use App\Http\Controllers\LoadingReportController;
use App\Http\Controllers\MainChallanController;
use App\Http\Controllers\PackageTypeController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\TruckTypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/onboarding')
    ->middleware(['onboarding.signature'])
    ->group(function (): void {
        Route::post('create-user', [ApiOnboardingController::class, 'createUser']);
        Route::post('update-user', [ApiOnboardingController::class, 'updateUser']);
        Route::get('status', [ApiOnboardingController::class, 'status']);
    });

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});

// Password change — any authenticated user
Route::middleware('auth')->group(function () {
    Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
});

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('select-branch', [BranchContextController::class, 'select'])->name('branch-context.select');
    Route::post('session/branch', [BranchContextController::class, 'store'])->name('branch-context.store');
    Route::delete('session/branch', [BranchContextController::class, 'destroy'])->name('branch-context.destroy');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('companies', CompanyController::class);

    Route::middleware('role:super_admin')->group(function () {
        Route::prefix('subscription')->name('subscription.')->group(function () {
            Route::get('/', [SubscriptionController::class, 'index'])->name('index');
            Route::get('/manage', [SubscriptionController::class, 'show'])->name('show');
            Route::get('/checkout', [SubscriptionController::class, 'checkoutForm'])->name('checkout.form');
            Route::get('/history', [SubscriptionController::class, 'history'])->name('history');
            Route::post('/quote', [SubscriptionController::class, 'quote'])->name('quote');
            Route::post('/promo/validate', [SubscriptionController::class, 'validatePromo'])->name('promo.validate');
            Route::post('/checkout', [SubscriptionController::class, 'checkout'])->name('checkout');
            Route::post('/callback', [SubscriptionController::class, 'callback'])->name('callback');
        });
    });

    Route::middleware('subscription.active')->group(function () {
        Route::resource('branches', BranchController::class);
        Route::get('branches/{branch}/settings', [BranchSettingsController::class, 'edit'])->name('branches.settings.edit');
        Route::put('branches/{branch}/settings', [BranchSettingsController::class, 'update'])->name('branches.settings.update');
        Route::put('trucks/{truck}/complete-tour', [TruckController::class, 'completeTour'])->name('trucks.complete-tour');
        Route::get('trucks/quick/form-data', [TruckController::class, 'quickFormData'])->name('trucks.quick-form-data');
        Route::post('trucks/quick', [TruckController::class, 'quickStore'])->name('trucks.quick-store');
        Route::resource('trucks', TruckController::class);
        Route::get('cities/by-state/{state}', [CityController::class, 'byState'])->name('cities.by-state');
        Route::get('cities/quick/form-data', [CityController::class, 'quickFormData'])->name('cities.quick-form-data');
        Route::post('cities/quick', [CityController::class, 'quickStore'])->name('cities.quick-store');
        Route::post('countries/quick', [CountryController::class, 'quickStore'])->name('countries.quick-store');
        Route::get('states/quick/form-data', [StateController::class, 'quickFormData'])->name('states.quick-form-data');
        Route::post('states/quick', [StateController::class, 'quickStore'])->name('states.quick-store');
        Route::get('parties/quick/form-data', [PartyController::class, 'quickFormData'])->name('parties.quick-form-data');
        Route::get('parties/{party}/quick', [PartyController::class, 'quickShow'])->name('parties.quick');
        Route::post('parties/quick', [PartyController::class, 'quickStore'])->name('parties.quick-store');
        Route::resource('parties', PartyController::class);
        Route::get('stations/quick/form-data', [StationController::class, 'quickFormData'])->name('stations.quick-form-data');
        Route::get('stations/{station}/quick', [StationController::class, 'quickShow'])->name('stations.quick');
        Route::post('stations/quick', [StationController::class, 'quickStore'])->name('stations.quick-store');
        Route::resource('stations', StationController::class);
        Route::get('bilties/{bilty}/print', [BiltyController::class, 'print'])->name('bilties.print');
        Route::resource('bilties', BiltyController::class);

        Route::get('challans/{challan}/print', [ChallanController::class, 'print'])->name('challans.print');
        Route::resource('challans', ChallanController::class);

        Route::get('main-challans/{main_challan}/print', [MainChallanController::class, 'print'])->name('main-challans.print');
        Route::resource('main-challans', MainChallanController::class);

        // Master Configuration
        Route::post('truck-types/quick', [TruckTypeController::class, 'quickStore'])->name('truck-types.quick-store');
        Route::resource('truck-types', TruckTypeController::class);
        Route::post('groups/quick', [GroupController::class, 'quickStore'])->name('groups.quick-store');
        Route::resource('groups', GroupController::class);
        Route::post('goods/quick', [GoodController::class, 'quickStore'])->name('goods.quick-store');
        Route::resource('goods', GoodController::class);
        Route::post('package-types/quick', [PackageTypeController::class, 'quickStore'])->name('package-types.quick-store');
        Route::resource('package-types', PackageTypeController::class);
        Route::get('agents/quick/form-data', [AgentController::class, 'quickFormData'])->name('agents.quick-form-data');
        Route::post('agents/quick', [AgentController::class, 'quickStore'])->name('agents.quick-store');
        Route::resource('agents', AgentController::class);
        Route::resource('bank-accounts', BankAccountController::class);

        // Accounting (new Tally-style stack)
        Route::get('accounting/account-options', AccountOptionsController::class)->name('accounting.account-options');
        Route::resource('accounting/account-groups', AccountGroupController::class)->only(['index', 'store', 'update', 'destroy'])->names('accounting.account-groups');
        Route::resource('accounting/accounts', AccountController::class)->only(['index', 'show', 'store', 'update', 'destroy'])->names('accounting.accounts');
        Route::resource('accounting/financial-years', FinancialYearController::class)->only(['index', 'store', 'update'])->names('accounting.financial-years');
        Route::post('accounting/financial-years/{financial_year}/set-current', [FinancialYearController::class, 'setCurrent'])->name('accounting.financial-years.set-current');
        Route::get('accounting/opening-balances/create', [OpeningBalanceController::class, 'create'])->name('accounting.opening-balances.create');
        Route::post('accounting/opening-balances', [OpeningBalanceController::class, 'store'])->name('accounting.opening-balances.store');
        Route::get('accounting/vouchers', [AccountingVoucherController::class, 'index'])->name('accounting.vouchers.index');
        Route::get('accounting/vouchers/create', [AccountingVoucherController::class, 'create'])->name('accounting.vouchers.create');
        Route::post('accounting/vouchers', [AccountingVoucherController::class, 'store'])->name('accounting.vouchers.store');
        Route::get('accounting/vouchers/{voucher}', [AccountingVoucherController::class, 'show'])->name('accounting.vouchers.show');
        Route::post('accounting/vouchers/{voucher}/cancel', [AccountingVoucherController::class, 'cancel'])->name('accounting.vouchers.cancel');
        Route::post('accounting/vouchers/{voucher}/reverse', [AccountingVoucherController::class, 'reverse'])->name('accounting.vouchers.reverse');
        Route::get('accounting/reports/ledger', [AccountingReportController::class, 'ledger'])->name('accounting.reports.ledger');
        Route::get('accounting/reports/day-book', [AccountingReportController::class, 'dayBook'])->name('accounting.reports.day-book');
        Route::get('accounting/reports/cash-book', [AccountingReportController::class, 'cashBook'])->name('accounting.reports.cash-book');
        Route::get('accounting/reports/bank-book', [AccountingReportController::class, 'bankBook'])->name('accounting.reports.bank-book');
        Route::get('accounting/reports/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('accounting.reports.trial-balance');
        Route::get('accounting/reports/profit-and-loss', [AccountingReportController::class, 'profitAndLoss'])->name('accounting.reports.profit-and-loss');
        Route::get('accounting/reports/balance-sheet', [AccountingReportController::class, 'balanceSheet'])->name('accounting.reports.balance-sheet');
        Route::get('accounting/reports/receivables', [AccountingReportController::class, 'receivables'])->name('accounting.reports.receivables');
        Route::get('accounting/reports/payables', [AccountingReportController::class, 'payables'])->name('accounting.reports.payables');

        // Administration
        Route::resource('users', UserController::class);
        Route::get('loading-reports/{loading_report}/print', [LoadingReportController::class, 'print'])->name('loading-reports.print');
        Route::resource('loading-reports', LoadingReportController::class);
        Route::get('vouchers/entity-balance', [VoucherController::class, 'entityBalance'])->name('vouchers.entity-balance');
        Route::resource('vouchers', VoucherController::class);
        Route::resource('inward-registers', InwardRegisterController::class);

        Route::get('delivery-receipts/gr-lookup', [DeliveryReceiptController::class, 'lookupGr'])->name('delivery-receipts.gr-lookup');
        Route::get('delivery-receipts/{delivery_receipt}/print', [DeliveryReceiptController::class, 'print'])->name('delivery-receipts.print');
        Route::resource('delivery-receipts', DeliveryReceiptController::class);

        Route::get('invoices/pending-bilties', [\App\Http\Controllers\InvoiceController::class, 'pendingBilties'])->name('invoices.pending-bilties');
        Route::get('invoices/{invoice}/print', [\App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
        Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);

        // Reports
        Route::get('reports/bilty-register', [\App\Http\Controllers\ReportController::class, 'biltyRegister'])->name('reports.bilty-register');
        Route::get('reports/challan-register', [\App\Http\Controllers\ReportController::class, 'challanRegister'])->name('reports.challan-register');
        Route::get('reports/party-ledger', [\App\Http\Controllers\LedgerReportController::class, 'partyLedger'])->name('reports.party-ledger');
        Route::get('reports/agent-ledger', [\App\Http\Controllers\LedgerReportController::class, 'agentLedger'])->name('reports.agent-ledger');
        Route::get('reports/cash-bank-book', [\App\Http\Controllers\LedgerReportController::class, 'cashBankBook'])->name('reports.cash-bank-book');

        Route::resource('roles', RoleController::class);
    });
});
