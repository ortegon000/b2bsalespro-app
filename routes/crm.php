<?php

use App\Http\Controllers\Crm\ContactTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:access-crm'])->prefix('crm')->name('crm.')->group(function () {
    Route::livewire('/', 'pages::crm.pipeline')->name('pipeline');
    Route::livewire('companies/create', 'pages::crm.company-form')->name('companies.create');
    Route::get('contacts/template', ContactTemplateController::class)->name('contacts.template');
    Route::livewire('companies/{company}', 'pages::crm.company')->name('companies.show');
    Route::livewire('companies/{company}/contacts/import', 'pages::crm.contacts-import')->name('companies.contacts.import');
    Route::livewire('companies/{company}/edit', 'pages::crm.company-form')->name('companies.edit');
});
