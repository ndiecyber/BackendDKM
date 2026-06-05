<?php

use App\Http\Controllers\Api\V1\Keuangan\BankKasController;
use App\Http\Controllers\Api\V1\Keuangan\CategoryController;
use App\Http\Controllers\Api\V1\Keuangan\DashboardController;
use App\Http\Controllers\Api\V1\Keuangan\ReportController;
use App\Http\Controllers\Api\V1\Keuangan\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Keuangan Module Routes
|--------------------------------------------------------------------------
|
| All routes here are prefixed with /api/v1/keuangan
| and protected by auth:sanctum middleware (applied in api.php).
|
*/

// Categories
Route::apiResource('categories', CategoryController::class);
Route::patch('categories/{id}/restore', [CategoryController::class, 'restore']);

// Bank/Kas
Route::apiResource('bank-kas', BankKasController::class);
Route::patch('bank-kas/{id}/restore', [BankKasController::class, 'restore']);
Route::post('bank-kas/{id}/adjust', [BankKasController::class, 'adjust']);

// Transactions
Route::apiResource('transactions', TransactionController::class);
Route::patch('transactions/{id}/restore', [TransactionController::class, 'restore']);
Route::patch('transactions/{id}/status', [TransactionController::class, 'updateStatus']);

// Dashboard
Route::prefix('dashboard')->group(function () {
    Route::get('/overview', [DashboardController::class, 'overview']);
    Route::get('/chart/income-vs-expense', [DashboardController::class, 'chartPemasukanVsPengeluaran']);
    Route::get('/chart/income-composition', [DashboardController::class, 'chartKomposisiPemasukan']);
    Route::get('/chart/expense-composition', [DashboardController::class, 'chartKomposisiPengeluaran']);
});

// Reports
Route::prefix('reports')->group(function () {
    Route::get('/buku-kas', [ReportController::class, 'bukuKasUmum']);
    Route::get('/rekap-kategori', [ReportController::class, 'rekapKategori']);
    Route::get('/export/csv', [ReportController::class, 'exportCsv']);
    Route::get('/export/pdf', [ReportController::class, 'exportPdf']);
});
