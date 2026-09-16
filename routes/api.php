<?php

use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas API para el Sistema de Gestión de Ventas Masivas (ETL & Analytics)
|
*/

// A. & B. Gestión de Importaciones (CRUD y ETL)
Route::prefix('imports')->group(function () {
    Route::get('/', [ImportController::class, 'index'])->name('api.imports.index');
    Route::post('/', [ImportController::class, 'store'])->name('api.imports.store');
    Route::get('/{id}', [ImportController::class, 'show'])->name('api.imports.show');
    Route::get('/{id}/errors', [ImportController::class, 'errors'])->name('api.imports.errors');
    Route::delete('/{id}', [ImportController::class, 'destroy'])->name('api.imports.destroy');
});

// C. Reporte de Inteligencia de Negocio
Route::prefix('reports')->group(function () {
    Route::get('/summary', [ReportController::class, 'summary'])->name('api.reports.summary');
});
