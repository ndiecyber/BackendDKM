<?php

use App\Http\Controllers\Api\V1\WebProfile\AnnouncementController;
use App\Http\Controllers\Api\V1\WebProfile\DashboardController;
use App\Http\Controllers\Api\V1\WebProfile\EventController;
use App\Http\Controllers\Api\V1\WebProfile\FinanceSummaryController;
use App\Http\Controllers\Api\V1\WebProfile\GalleryController;
use App\Http\Controllers\Api\V1\WebProfile\ServiceController;
use App\Http\Controllers\Api\V1\WebProfile\SettingController;
use App\Http\Controllers\Api\V1\WebProfile\VisitorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Profile Module Routes
|--------------------------------------------------------------------------
|
| All routes here are prefixed with /api/v1/web-profile
|
*/

// Public routes (No authentication required)
Route::get('/settings', [SettingController::class, 'show']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('/galleries', [GalleryController::class, 'index']);
Route::get('/galleries/{id}', [GalleryController::class, 'show']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::get('/announcements', [AnnouncementController::class, 'index']);
Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);

// Dashboard stats for public (or admin)
Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
Route::get('/dashboard/visitors-chart', [DashboardController::class, 'visitorsChart']);

// Visitor tracking
Route::post('/visitors', [VisitorController::class, 'store']);

// Finance Summary Widget
Route::get('/finance-summary', [FinanceSummaryController::class, 'index']);

// Protected routes (Admin Web Profile)
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/settings', [SettingController::class, 'update']);

    // CRUD for Services
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

    // CRUD for Galleries
    Route::post('/galleries', [GalleryController::class, 'store']);
    Route::put('/galleries/{id}', [GalleryController::class, 'update']);
    Route::delete('/galleries/{id}', [GalleryController::class, 'destroy']);

    // CRUD for Events
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);

    // CRUD for Announcements
    Route::post('/announcements', [AnnouncementController::class, 'store']);
    Route::put('/announcements/{id}', [AnnouncementController::class, 'update']);
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);
});
