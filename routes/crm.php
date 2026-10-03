<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:access-crm'])->prefix('crm')->name('crm.')->group(function () {
    Route::livewire('/', 'pages::crm.pipeline')->name('pipeline');
});
