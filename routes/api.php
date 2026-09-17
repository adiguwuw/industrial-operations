<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IncidentCategoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\TaskController;

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
    Route::post('/checklists', [ChecklistController::class, 'store']);
    Route::post('/checklists/{checklist}/items', [ChecklistController::class, 'storeItem']);
    Route::post('/inspections', [ChecklistController::class, 'storeInspection']);
    Route::patch('/inspections/{inspection}/items/{item}',[ChecklistController::class, 'updateInspectionItemResult']);
    Route::post('/inspections/{inspection}/items/{item}/findings',[ChecklistController::class, 'storeFinding']);
    Route::post('/findings/{finding}/risk-assessments',[ChecklistController::class, 'storeRiskAssessment']);
    Route::post('/findings/{finding}/capas',[ChecklistController::class, 'storeCapa']);
    Route::patch('/capas/{capa}/status', [ChecklistController::class, 'updateCapaStatus']);
    Route::patch('/capas/{capa}/verify', [ChecklistController::class, 'verifyCapa']);
    Route::post('/capas/{capa}/tasks', [ChecklistController::class, 'storeTask']);
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);
});
