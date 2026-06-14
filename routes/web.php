<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MeterController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\MeterReplacementController;
use App\Http\Controllers\ParcelController;
use App\Http\Controllers\ParcelTenantController;
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
    Route::patch('members/{member}/archive', [MemberController::class, 'archive'])
        ->name('members.archive');
    Route::resource('members', MemberController::class)->except('destroy');
    Route::resource('parcels', ParcelController::class)->except('destroy');
    Route::get('meters/{meter}/replace', [MeterReplacementController::class, 'create'])
        ->name('meters.replace');
    Route::post('meters/{meter}/replace', [MeterReplacementController::class, 'store'])
        ->name('meters.replace.store');
    Route::resource('meters', MeterController::class)->except('destroy');
    Route::resource('meter-readings', MeterReadingController::class)->only(['create', 'store']);
    Route::resource('parcel-tenants', ParcelTenantController::class)
        ->only(['create', 'store', 'edit', 'update'])
        ->parameters(['parcel-tenants' => 'parcel_tenant']);
});
