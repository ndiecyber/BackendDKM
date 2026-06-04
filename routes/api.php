<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
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

        // User Management
        Route::apiResource('users', UserController::class);
        Route::patch('users/{id}/restore', [UserController::class, 'restore']);
        Route::patch('users/{id}/reset-password', [UserController::class, 'resetPassword']);

        // Future module routes:
        // Route::prefix('keuangan')->group(base_path('routes/modules/keuangan.php'));
        // Route::prefix('kurban')->group(base_path('routes/modules/kurban.php'));
        // Route::prefix('profile')->group(base_path('routes/modules/profile.php'));
    });
});
