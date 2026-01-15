<?php

use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});





Route::get('/configuration', [ConfigurationController::class, 'index'])
    ->name('configuration.index');

Route::get('/configuration/applications/{industry}', [ConfigurationController::class, 'getApplications'])
    ->name('configuration.applications');

Route::post('/configuration/generate', [ConfigurationController::class, 'generate'])
    ->name('configuration.generate');

Route::post('/configuration/export-pdf', [ConfigurationController::class, 'exportPdf'])
    ->name('configuration.export-pdf');



require __DIR__.'/auth.php';
