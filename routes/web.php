<?php

use App\Http\Controllers\Api\OnboardingController as ApiOnboardingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BiltyController;
use App\Http\Controllers\BranchContextController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ChallanController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryReceiptController;
use App\Http\Controllers\InwardRegisterController;
use App\Http\Controllers\LoadingReportController;
use App\Http\Controllers\MainChallanController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TransporterController;
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
        Route::resource('transporters', TransporterController::class);
        Route::put('trucks/{truck}/complete-tour', [TruckController::class, 'completeTour'])->name('trucks.complete-tour');
        Route::resource('trucks', TruckController::class);
        Route::get('cities/by-state/{state}', [CityController::class, 'byState'])->name('cities.by-state');
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
        Route::resource('truck-types', TruckTypeController::class);
        Route::resource('groups', App\Http\Controllers\GroupController::class);
        Route::resource('goods', App\Http\Controllers\GoodController::class);
        Route::resource('package-types', App\Http\Controllers\PackageTypeController::class);
        Route::resource('agents', App\Http\Controllers\AgentController::class);
        Route::resource('vendors', App\Http\Controllers\VendorController::class);

        // Administration
        Route::resource('users', UserController::class);
        Route::get('loading-reports/{loading_report}/print', [LoadingReportController::class, 'print'])->name('loading-reports.print');
        Route::resource('loading-reports', LoadingReportController::class);
        Route::resource('vouchers', VoucherController::class);
        Route::resource('inward-registers', InwardRegisterController::class);

        Route::get('delivery-receipts/gr-lookup', [DeliveryReceiptController::class, 'lookupGr'])->name('delivery-receipts.gr-lookup');
        Route::get('delivery-receipts/{delivery_receipt}/print', [DeliveryReceiptController::class, 'print'])->name('delivery-receipts.print');
        Route::resource('delivery-receipts', DeliveryReceiptController::class);

        Route::get('invoices/pending-bilties', [\App\Http\Controllers\InvoiceController::class, 'pendingBilties'])->name('invoices.pending-bilties');
        Route::get('invoices/{invoice}/payments', [\App\Http\Controllers\InvoiceController::class, 'payments'])->name('invoices.payments.index');
        Route::post('invoices/{invoice}/payments', [\App\Http\Controllers\InvoiceController::class, 'storePayment'])->name('invoices.payments.store');
        Route::get('invoices/{invoice}/print', [\App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
        Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);

        // Reports
        Route::get('reports/bilty-register', [\App\Http\Controllers\ReportController::class, 'biltyRegister'])->name('reports.bilty-register');
        Route::get('reports/challan-register', [\App\Http\Controllers\ReportController::class, 'challanRegister'])->name('reports.challan-register');
        Route::get('reports/party-ledger', [\App\Http\Controllers\ReportController::class, 'partyLedger'])->name('reports.party-ledger');

        Route::resource('roles', RoleController::class);
    });
});
