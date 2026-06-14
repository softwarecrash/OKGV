<?php

use App\Http\Controllers\BillingPeriodController;
use App\Http\Controllers\BillingRateAssignmentController;
use App\Http\Controllers\BillingRateController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MeterController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\MeterReadingCorrectionController;
use App\Http\Controllers\MeterReplacementController;
use App\Http\Controllers\ParcelController;
use App\Http\Controllers\ParcelTenantController;
use App\Http\Controllers\UserPermissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('home')
        : redirect()->route('login');
});

Auth::routes([
    'register' => false,
    'verify' => false,
]);

Route::get('/dashboard', [HomeController::class, 'index'])
    ->middleware('auth')
    ->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('user-permissions', [UserPermissionController::class, 'index'])
        ->name('user-permissions.index');
    Route::put('user-permissions/{user}', [UserPermissionController::class, 'update'])
        ->name('user-permissions.update');
    Route::post('billing-periods/{billing_period}/calculate', [BillingPeriodController::class, 'calculate'])
        ->name('billing-periods.calculate');
    Route::post('billing-periods/{billing_period}/approve', [BillingPeriodController::class, 'approve'])
        ->name('billing-periods.approve');
    Route::post('billing-periods/{billing_period}/archive', [BillingPeriodController::class, 'archive'])
        ->name('billing-periods.archive');
    Route::resource('billing-periods', BillingPeriodController::class)->except('destroy');
    Route::resource('billing-periods.billing-rates', BillingRateController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['billing-rates' => 'billing_rate']);
    Route::post('billing-rates/{billing_rate}/assignments', [BillingRateAssignmentController::class, 'store'])
        ->name('billing-rate-assignments.store');
    Route::delete('billing-rate-assignments/{billing_rate_assignment}', [BillingRateAssignmentController::class, 'destroy'])
        ->name('billing-rate-assignments.destroy');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
        ->name('invoices.pdf');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);
    Route::patch('members/{member}/archive', [MemberController::class, 'archive'])
        ->name('members.archive');
    Route::resource('members', MemberController::class)->except('destroy');
    Route::resource('parcels', ParcelController::class)->except('destroy');
    Route::get('meters/{meter}/replace', [MeterReplacementController::class, 'create'])
        ->name('meters.replace');
    Route::post('meters/{meter}/replace', [MeterReplacementController::class, 'store'])
        ->name('meters.replace.store');
    Route::resource('meters', MeterController::class)->except('destroy');
    Route::get('meter-readings/{meter_reading}/corrections/create', [MeterReadingCorrectionController::class, 'create'])
        ->name('meter-reading-corrections.create');
    Route::post('meter-readings/{meter_reading}/corrections', [MeterReadingCorrectionController::class, 'store'])
        ->name('meter-reading-corrections.store');
    Route::resource('meter-readings', MeterReadingController::class)->only(['create', 'store']);
    Route::resource('parcel-tenants', ParcelTenantController::class)
        ->only(['create', 'store', 'edit', 'update'])
        ->parameters(['parcel-tenants' => 'parcel_tenant']);
});
