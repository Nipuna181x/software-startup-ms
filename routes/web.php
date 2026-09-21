<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');

Route::livewire('register', 'pages::auth.register')
    ->middleware('guest')
    ->name('register');

Route::middleware(['auth', 'active'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
