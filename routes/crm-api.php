<?php

use App\Http\Controllers\Crm\LeadIntakeController;
use App\Http\Middleware\EnsureValidCrmIntakeToken;
use Illuminate\Support\Facades\Route;

Route::middleware([EnsureValidCrmIntakeToken::class, 'throttle:60,1'])->group(function () {
    Route::post('leads', LeadIntakeController::class)->name('crm.leads.store');
});
