<?php

use App\Http\Controllers\Crm\BrevoWebhookController;
use App\Http\Controllers\Crm\LeadIntakeController;
use App\Http\Middleware\EnsureValidCrmToken;
use Illuminate\Support\Facades\Route;

Route::post('leads', LeadIntakeController::class)
    ->middleware([EnsureValidCrmToken::class.':crm.intake_token', 'throttle:60,1'])
    ->name('crm.leads.store');

Route::post('brevo/webhook', BrevoWebhookController::class)
    ->middleware(EnsureValidCrmToken::class.':crm.brevo.webhook_token')
    ->name('crm.brevo.webhook');
