<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\JamaahController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\Keuangan\BalanceAdjustmentController;
use App\Http\Controllers\Api\V1\Keuangan\BankKasController;
use App\Http\Controllers\Api\V1\Keuangan\CategoryController;
use App\Http\Controllers\Api\V1\Keuangan\ReportController;
use App\Http\Controllers\Api\V1\Keuangan\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Shared Backend API for DKM applications.
| All routes are prefixed with /api/v1/
|
*/

Route::prefix('v1')->group(function () {
    /*
    |----------------------------------------------------------------------
    | Public Routes
    |----------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    /*
    |----------------------------------------------------------------------
    | Protected Routes (require auth:sanctum)
    |----------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
        });

        // Transaction Management
        Route::apiResource('transactions', TransactionController::class);

        // Bank/Kas Management
        Route::apiResource('bank-kas', BankKasController::class);
        Route::get('bank-kas/{bank_kas}/adjustments', [BalanceAdjustmentController::class, 'index']);
        Route::post('bank-kas/{bank_kas}/adjustments', [BalanceAdjustmentController::class, 'store']);

        // User Management
        Route::apiResource('users', UserController::class);
        Route::patch('users/{id}/restore', [UserController::class, 'restore']);
        Route::patch('users/{id}/reset-password', [UserController::class, 'resetPassword']);

        // Jamaah
        Route::apiResource('jamaah', JamaahController::class);
        Route::patch('jamaah/{id}/restore', [JamaahController::class, 'restore']);

        // Future module routes:
        Route::prefix('keuangan')->group(base_path('routes/modules/keuangan.php'));
        // Route::prefix('kurban')->group(base_path('routes/modules/kurban.php'));
        // Route::prefix('profile')->group(base_path('routes/modules/profile.php'));
    });
});
