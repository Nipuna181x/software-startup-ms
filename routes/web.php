<?php

use App\Http\Controllers\AdminOrganizationController;
use App\Http\Controllers\AdminSessionController;
use App\Http\Middleware\EnsurePlatformAdmin;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');

Route::livewire('register', 'pages::auth.register')
    ->middleware('guest')
    ->name('register');

Route::middleware(['auth', 'active'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/calling.php';

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminSessionController::class, 'create'])->name('login');
    Route::post('login', [AdminSessionController::class, 'store'])->middleware('throttle:30,1')->name('login.store');

    Route::middleware(EnsurePlatformAdmin::class)->group(function () {
        Route::get('/', [AdminOrganizationController::class, 'index'])->name('dashboard');
        Route::get('organizations/{organization}', [AdminOrganizationController::class, 'show'])->name('organizations.show');
        Route::post('logout', [AdminSessionController::class, 'destroy'])->name('logout');
    });
});
