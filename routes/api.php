<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\JamaahController;

use App\Http\Controllers\Api\V1\ProfileController;
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

        // Profile Management (Authenticated User)
        Route::put('/profile', [ProfileController::class, 'updateProfile']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);



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

    // Web Profile Module (contains both public and protected routes inside)
    Route::prefix('web-profile')->group(base_path('routes/modules/web_profile.php'));
});
