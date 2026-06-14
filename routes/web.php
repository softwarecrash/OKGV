<?php

use App\Http\Controllers\HomeController;
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
