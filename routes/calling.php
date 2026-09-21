<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->prefix('calling')->name('calling.')->group(function () {
    Route::livewire('/', 'pages::calling.categories.index')->name('categories.index');

    Route::livewire('categories/{category}', 'pages::calling.types.index')->name('types.index');
});
