<?php

use App\Http\Controllers\Api\V1\Keuangan\BalanceAdjustmentController;
use App\Http\Controllers\Api\V1\Keuangan\BankKasController;
use App\Http\Controllers\Api\V1\Keuangan\CategoryController;
use App\Http\Controllers\Api\V1\Keuangan\DashboardController;
use App\Http\Controllers\Api\V1\Keuangan\ProgramController;
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

// Programs
Route::get('programs/trashed', [ProgramController::class, 'trashed']);
Route::apiResource('programs', ProgramController::class);
Route::patch('programs/{id}/restore', [ProgramController::class, 'restore']);
Route::get('programs/{id}/physical-balances', [ProgramController::class, 'physicalBalances']);
Route::post('programs/{id}/rollover', [ProgramController::class, 'rollover']);
Route::delete('programs/{id}/force', [ProgramController::class, 'forceDelete']);

// Categories
Route::post('categories/batch', [CategoryController::class, 'batchUpdate']);
Route::get('categories/trashed', [CategoryController::class, 'trashed']);
Route::apiResource('categories', CategoryController::class);
Route::patch('categories/{id}/restore', [CategoryController::class, 'restore']);
Route::delete('categories/{id}/force', [CategoryController::class, 'forceDelete']);

// Bank/Kas
Route::get('bank-kas/trashed', [BankKasController::class, 'trashed']);
Route::apiResource('bank-kas', BankKasController::class);
Route::patch('bank-kas/{id}/restore', [BankKasController::class, 'restore']);
Route::get('bank-kas/{bank_kas}/adjustments', [BalanceAdjustmentController::class, 'index']);
Route::post('bank-kas/{bank_kas}/adjustments', [BalanceAdjustmentController::class, 'store']);
Route::get('bank-kas/{id}/activities', [BankKasController::class, 'activities']);
Route::get('bank-kas/{id}/program-balances', [BankKasController::class, 'programBalances']);
Route::delete('bank-kas/{id}/force', [BankKasController::class, 'forceDelete']);

// Transactions
Route::get('transactions/trashed', [TransactionController::class, 'trashed']);
Route::apiResource('transactions', TransactionController::class);
Route::patch('transactions/{id}/restore', [TransactionController::class, 'restore']);
Route::patch('transactions/{id}/status', [TransactionController::class, 'updateStatus']);
Route::delete('transactions/{id}/force', [TransactionController::class, 'forceDelete']);

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
