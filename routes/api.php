<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IncidentCategoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\UserController;
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
    Route::get('/incident-categories', [IncidentCategoryController::class, 'index']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);});
    Route::get('users/{user}/photo', [UserController::class, 'photo'])->name('users.photo');

    
    
});
