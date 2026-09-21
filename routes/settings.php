<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');

    Route::livewire('settings/organization', 'pages::settings.organization')
        ->middleware('can:super-admin')
        ->name('organization.edit');

    Route::livewire('users', 'pages::users.index')
        ->middleware('can:super-admin')
        ->name('users.index');
});
