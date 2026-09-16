<?php

use App\Http\Controllers\Web\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Rutas Web para la interfaz de usuario Blade (Dashboard, Carga y Reportes)
|
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/imports/{id}/detail', [DashboardController::class, 'detail'])->name('imports.detail');
Route::get('/imports/{id}/report', [DashboardController::class, 'report'])->name('imports.report');
Route::get('/imports/{id}', [DashboardController::class, 'report'])->name('imports.show');
Route::get('/samples/generate', [DashboardController::class, 'generateSample'])->name('samples.generate');
