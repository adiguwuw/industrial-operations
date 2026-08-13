<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IncidentController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('incidents', IncidentController::class)->only([
        'index', 'store', 'show'    
        ]);

    Route::post('incidents/{incident}/investigate', [IncidentController::class, 'investigate']);
    Route::post('incidents/{incident}/resolve', [IncidentController::class, 'resolve']);
    Route::get('/incidents/{incident}/comments',[IncidentController::class, 'comments']);
    Route::post('/incidents/{incident}/comments',[IncidentController::class, 'storeComment']);
    Route::get('/incidents/{incident}/photos',[IncidentController::class, 'photos']);
    Route::post('/incidents/{incident}/photos',[IncidentController::class, 'storePhoto']);
    Route::delete('/incidents/{incident}/photos/{photo}',[IncidentController::class, 'destroyPhoto']);
});