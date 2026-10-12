<?php

use App\Http\Controllers\Api\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', LoginController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', \App\Http\Controllers\Api\Auth\LogoutController::class);
        Route::get('/auth/me', \App\Http\Controllers\Api\Auth\MeController::class);
    });
});
