<?php

use App\Http\Controllers\Api\V1\Qurban\AnimalGroupController;
use App\Http\Controllers\Api\V1\Qurban\DashboardController;
use App\Http\Controllers\Api\V1\Qurban\PeriodController;
use App\Http\Controllers\Api\V1\Qurban\QurbanTransactionController;
use App\Http\Controllers\Api\V1\Qurban\ShohibulController;
use App\Http\Controllers\Api\V1\Qurban\WebhookController;
use App\Services\Qurban\RolloverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Qurban Module Routes
|--------------------------------------------------------------------------
|
| All routes here are prefixed with /api/v1/qurban (applied in api.php).
| Public routes are accessible without authentication.
| Admin routes require auth:sanctum middleware.
|
*/

// ===== PUBLIC ROUTES =====

// Config / Period
Route::get('config/active', [PeriodController::class, 'active']);

// Dashboard
Route::get('dashboard/stats', [DashboardController::class, 'stats']);

// Shohibuls
Route::get('shohibuls', [ShohibulController::class, 'index']);
Route::get('shohibuls/search', [ShohibulController::class, 'search']);
Route::post('shohibuls/register', [ShohibulController::class, 'register']);
Route::get('shohibuls/{id}', [ShohibulController::class, 'show']);

// Transactions
Route::get('transactions', [QurbanTransactionController::class, 'index']);
Route::post('transactions/deposit', [QurbanTransactionController::class, 'deposit']);

// Groups
Route::get('groups', [AnimalGroupController::class, 'index']);

// Webhook (no auth — validated via PaKasir double-check)
Route::post('webhook/pakasir', [WebhookController::class, 'pakasir']);

// ===== ADMIN ROUTES (require auth:sanctum) =====

Route::middleware('auth:sanctum')->group(function () {
    // Periods
    Route::get('admin/periods', [PeriodController::class, 'index']);
    Route::post('admin/periods', [PeriodController::class, 'store']);
    Route::put('admin/periods/active', [PeriodController::class, 'update']);

    // Shohibuls
    Route::put('admin/shohibuls/{id}', [ShohibulController::class, 'update']);
    Route::delete('admin/shohibuls/{id}', [ShohibulController::class, 'destroy']);

    // Groups
    Route::post('admin/groups', [AnimalGroupController::class, 'store']);
    Route::post('admin/groups/move-member', [AnimalGroupController::class, 'moveMember']);

    // Transactions
    Route::post('admin/transactions/manual', [QurbanTransactionController::class, 'manualDeposit']);
    Route::post('admin/transactions/{id}/cancel', [QurbanTransactionController::class, 'cancel']);

    // Rollover (Tutup Buku)
    Route::post('admin/rollover/execute', function (Request $request) {
        Gate::authorize('qurban.rollover.execute');

        $validated = $request->validate([
            'name' => ['required', 'string'],
            'sapi_price_per_slot' => ['required', 'numeric', 'min:0'],
            'kambing_price' => ['required', 'numeric', 'min:0'],
            'deadline_date' => ['required', 'date', 'after:today'],
        ]);

        $newPeriod = app(RolloverService::class)->execute($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tutup buku berhasil. Periode baru telah diaktifkan.',
            'data' => $newPeriod,
        ], 201);
    });
});
