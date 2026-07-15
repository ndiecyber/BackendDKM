<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\JamaahController;
use App\Http\Controllers\Api\V1\Keuangan\KeuanganSettingController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\DB;
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
    Route::get('/migrate', function () {
        return response()->json(DB::table('migrations')->get());
    });

    /*
    |----------------------------------------------------------------------
    | Public Routes
    |----------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::get('/keuangan/public/settings', [KeuanganSettingController::class, 'publicSettings']);
    Route::get('/keuangan/public/programs', [\App\Http\Controllers\Api\V1\Keuangan\ProgramController::class, 'publicPrograms']);
    Route::get('/keuangan/public/monthly-report', [\App\Http\Controllers\Api\V1\Keuangan\PublicReportController::class, 'monthlyReport']);

    // Qurban Module (contains both public and admin routes inside)
    Route::prefix('qurban')->group(base_path('routes/modules/kurban.php'));

    // Web Profile Module (contains both public and protected routes inside)
    Route::prefix('web-profile')->group(base_path('routes/modules/web_profile.php'));

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
        Route::apiResource('roles', RoleController::class);
        Route::patch('roles/{id}/move', [RoleController::class, 'move']);

        // Jamaah
        Route::apiResource('jamaah', JamaahController::class);
        Route::patch('jamaah/{id}/restore', [JamaahController::class, 'restore']);

        // Keuangan Module
        Route::prefix('keuangan')->group(base_path('routes/modules/keuangan.php'));
    });
});
