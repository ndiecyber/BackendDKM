<?php

use App\Http\Controllers\Api\V1\Keuangan\PublicDonationController;
use App\Http\Controllers\Api\V1\WebProfile\AnnouncementController;
use App\Http\Controllers\Api\V1\WebProfile\CommitteeController;
use App\Http\Controllers\Api\V1\WebProfile\CtaSettingController;
use App\Http\Controllers\Api\V1\WebProfile\DashboardController;
use App\Http\Controllers\Api\V1\WebProfile\EventController;
use App\Http\Controllers\Api\V1\WebProfile\FinanceSummaryController;
use App\Http\Controllers\Api\V1\WebProfile\GalleryController;
use App\Http\Controllers\Api\V1\WebProfile\MasterCategoryController;
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

// ──────────────────────────────────────────────────────────
// Public routes (No authentication required)
// ──────────────────────────────────────────────────────────

// Settings & Info
Route::get('/settings', [SettingController::class, 'show']);

// Services
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);

// Galleries
Route::get('/galleries', [GalleryController::class, 'index']);
Route::get('/galleries/{id}', [GalleryController::class, 'show']);

// Events / Kegiatan / Berita
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);

// Announcements
Route::get('/announcements', [AnnouncementController::class, 'index']);
Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);

// Committee / Pengurus DKM
Route::get('/committee', [CommitteeController::class, 'index']);

// CTA / Donasi
Route::get('/cta', [CtaSettingController::class, 'show']);
Route::get('/donations/programs', [PublicDonationController::class, 'getPrograms']);
Route::post('/donations', [PublicDonationController::class, 'store']);

// Master Data (kategori, label, tipe berita, status)
Route::get('/master-categories', [MasterCategoryController::class, 'index']);

// Dashboard stats (public or admin)
Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
Route::get('/dashboard/visitors-chart', [DashboardController::class, 'visitorsChart']);

// Visitor tracking
Route::post('/visitors', [VisitorController::class, 'store']);

// Finance Summary Widget
Route::get('/finance-summary', [FinanceSummaryController::class, 'index']);

// ──────────────────────────────────────────────────────────
// Protected routes (Admin Web Profile — require auth:sanctum)
// ──────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Settings
    Route::put('/settings', [SettingController::class, 'update']);

    // CRUD for Services
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

    // CRUD for Galleries
    Route::post('/galleries', [GalleryController::class, 'store']);
    Route::put('/galleries/{id}', [GalleryController::class, 'update']);
    Route::delete('/galleries/{id}', [GalleryController::class, 'destroy']);

    // CRUD for Events / Kegiatan / Berita
    Route::post('/events/upload-image', [EventController::class, 'uploadImage']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);

    // CRUD for Announcements
    Route::post('/announcements', [AnnouncementController::class, 'store']);
    Route::put('/announcements/{id}', [AnnouncementController::class, 'update']);
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);

    // Committee / Pengurus DKM
    Route::put('/committee', [CommitteeController::class, 'update']);
    Route::post('/committee/upload-photo', [CommitteeController::class, 'uploadPhoto']);

    // CTA / Donasi
    Route::put('/cta', [CtaSettingController::class, 'update']);

    // CRUD for Master Data
    Route::post('/master-categories', [MasterCategoryController::class, 'store']);
    Route::put('/master-categories/bulk', [MasterCategoryController::class, 'bulkUpdate']);
    Route::put('/master-categories/{id}', [MasterCategoryController::class, 'update']);
    Route::delete('/master-categories/{id}', [MasterCategoryController::class, 'destroy']);
});
