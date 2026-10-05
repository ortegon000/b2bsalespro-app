<?php

use App\Http\Controllers\Crm\CompanyTemplateController;
use App\Http\Controllers\Crm\ContactTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:access-crm'])->prefix('crm')->name('crm.')->group(function () {
    Route::livewire('/', 'pages::crm.pipeline')->name('pipeline');
    Route::livewire('companies/create', 'pages::crm.company-form')->name('companies.create');
    Route::livewire('companies/import', 'pages::crm.companies-import')->name('companies.import');
    Route::get('companies/template', CompanyTemplateController::class)->name('companies.template');
    Route::get('contacts/template', ContactTemplateController::class)->name('contacts.template');
    Route::livewire('companies/{company}', 'pages::crm.company')->name('companies.show');
    Route::livewire('companies/{company}/contacts/import', 'pages::crm.contacts-import')->name('companies.contacts.import');
    Route::livewire('companies/{company}/edit', 'pages::crm.company-form')->name('companies.edit');
    Route::livewire('companies/{company}/courses/create', 'pages::crm.course-form')->name('companies.courses.create');
    Route::livewire('courses/{course}', 'pages::crm.course')->name('courses.show');
    Route::livewire('courses/{course}/edit', 'pages::crm.course-form')->name('courses.edit');
    Route::livewire('sequences/{sequence}', 'pages::crm.sequence')->name('sequences.edit');
    Route::livewire('newsletter', 'pages::crm.newsletter')->name('newsletter');
    Route::livewire('tasks', 'pages::crm.tasks')->name('tasks');
});
