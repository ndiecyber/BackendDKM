<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TargetQurbanController; 
use App\Http\Controllers\PesertaQurbanController;
use App\Http\Controllers\SetoranController;

Route::apiResource('target-qurban', TargetQurbanController::class);
Route::apiResource('peserta', PesertaQurbanController::class);
Route::put('peserta/{id}/status', [PesertaQurbanController::class, 'updateStatus']);

Route::apiResource('setoran', SetoranController::class);
Route::put('setoran/{id}/status', [SetoranController::class, 'updateStatus']);