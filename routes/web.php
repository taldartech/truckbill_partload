<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\TransporterController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\InwardRegisterController;
use App\Http\Controllers\DeliveryReceiptController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\BiltyController;
use App\Http\Controllers\ChallanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\TruckTypeController;
use App\Http\Controllers\LoadingReportController;
use App\Http\Controllers\VoucherController;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('companies', CompanyController::class);
    Route::resource('branches', BranchController::class);
    Route::resource('transporters', TransporterController::class);
    Route::resource('trucks', TruckController::class);
    Route::resource('drivers', DriverController::class);
    Route::resource('stations', StationController::class);
    Route::resource('parties', PartyController::class);
    Route::get('bilties/{bilty}/print', [BiltyController::class, 'print'])->name('bilties.print');
    Route::resource('bilties', BiltyController::class);
    
    Route::get('challans/{challan}/print', [ChallanController::class, 'print'])->name('challans.print');
    Route::resource('challans', ChallanController::class);

    // Master Configuration
    Route::resource('truck-types', TruckTypeController::class);
    Route::resource('groups', App\Http\Controllers\GroupController::class);
    Route::resource('goods', App\Http\Controllers\GoodController::class);
    Route::resource('package-types', App\Http\Controllers\PackageTypeController::class);
    Route::resource('agents', App\Http\Controllers\AgentController::class);

    // Administration
    Route::resource('users', UserController::class);
    Route::resource('loading-reports', LoadingReportController::class);
    Route::resource('vouchers', VoucherController::class);
    Route::resource('inward-registers', InwardRegisterController::class);
    
    Route::get('delivery-receipts/{delivery_receipt}/print', [DeliveryReceiptController::class, 'print'])->name('delivery-receipts.print');
    Route::resource('delivery-receipts', DeliveryReceiptController::class);
    
    Route::get('invoices/{invoice}/print', [\App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
    
    // Reports
    Route::get('reports/bilty-register', [\App\Http\Controllers\ReportController::class, 'biltyRegister'])->name('reports.bilty-register');
    Route::get('reports/challan-register', [\App\Http\Controllers\ReportController::class, 'challanRegister'])->name('reports.challan-register');
    Route::get('reports/party-ledger', [\App\Http\Controllers\ReportController::class, 'partyLedger'])->name('reports.party-ledger');
    
    Route::resource('roles', RoleController::class);
    Route::resource('subscriptions', SubscriptionPlanController::class);
    
    Route::get('settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [\App\Http\Controllers\SettingController::class, 'store'])->name('settings.store');
});
