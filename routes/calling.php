<?php

use App\Http\Controllers\Calling\BusinessScreenshotController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->prefix('calling')->name('calling.')->scopeBindings()->group(function () {
    Route::livewire('/', 'pages::calling.categories.index')->name('categories.index');

    Route::livewire('categories/{category}', 'pages::calling.types.index')->name('types.index');

    Route::livewire('categories/{category}/types/{type}', 'pages::calling.businesses.index')
        ->name('businesses.index');

    Route::livewire('categories/{category}/types/{type}/businesses/{business}', 'pages::calling.businesses.show')
        ->name('businesses.show');

    Route::get('screenshots/{screenshot}', [BusinessScreenshotController::class, 'show'])
        ->name('screenshots.show');
});
