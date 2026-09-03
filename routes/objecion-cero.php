<?php

use App\Http\Controllers\ObjecionCero\PlantillaExportController;
use App\Http\Middleware\LogObjecionCeroView;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', LogObjecionCeroView::class])->prefix(config('objecion-cero.route_prefix'))->name('objecion-cero.')->group(function () {
    Route::livewire('/', 'pages::objecion-cero.inicio')->name('inicio');
    Route::livewire('banco', 'pages::objecion-cero.banco-fichas')->name('banco');
    Route::livewire('preguntas', 'pages::objecion-cero.preguntas')->name('preguntas');
    Route::livewire('frases', 'pages::objecion-cero.frases')->name('frases');
    Route::livewire('cierres', 'pages::objecion-cero.cierres')->name('cierres');
    Route::livewire('whatsapp', 'pages::objecion-cero.whatsapp')->name('whatsapp');
    Route::livewire('checklists', 'pages::objecion-cero.checklists')->name('checklists');
    Route::get('plantilla/exportar', PlantillaExportController::class)->name('plantilla.exportar');
    Route::livewire('plantilla', 'pages::objecion-cero.plantilla')->name('plantilla');
    Route::livewire('como-usar', 'pages::objecion-cero.como-usar')->name('como-usar');
});
