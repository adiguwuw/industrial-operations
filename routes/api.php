<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\DashboardController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/incidents/statistics', [IncidentController::class,'statistics',]);
    Route::get('/incidents/statistics/categories', [IncidentController::class,'statisticsByCategory',]);
    Route::get('/incidents/statistics/trends', [IncidentController::class,'statisticsTrends',]);
    Route::apiResource('incidents', IncidentController::class)->only(['index', 'store', 'show']);
    Route::post('incidents/{incident}/investigate', [IncidentController::class, 'investigate']);
    Route::post('incidents/{incident}/resolve', [IncidentController::class, 'resolve']);
    Route::get('/incidents/{incident}/comments',[IncidentController::class, 'comments']);
    Route::post('/incidents/{incident}/comments',[IncidentController::class, 'storeComment']);
    Route::get('/incidents/{incident}/photos',[IncidentController::class, 'photos']);
    Route::post('/incidents/{incident}/photos',[IncidentController::class, 'storePhoto']);
    Route::delete('/incidents/{incident}/photos/{photo}',[IncidentController::class, 'destroyPhoto']);
    Route::get('/incidents/{incident}/history',[IncidentController::class, 'history']);
    Route::get('/dashboard', [DashboardController::class,'index',]);
    Route::get('/incidents/{incident}/photos/{photo}/file',[IncidentController::class, 'file'])->name('incidents.photos.file');
    
});
