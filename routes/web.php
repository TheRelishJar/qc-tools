<?php
use App\Http\Controllers\ConfigurationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('configuration.index');
});

Route::get('/configuration', [ConfigurationController::class, 'index'])
    ->name('configuration.index');

Route::get('/configuration/applications/{industry}', [ConfigurationController::class, 'getApplications'])
    ->name('configuration.applications');

Route::post('/configuration/generate', [ConfigurationController::class, 'generate'])
    ->name('configuration.generate');

Route::post('/configuration/export-pdf', [ConfigurationController::class, 'exportPdf'])
    ->name('configuration.export-pdf');