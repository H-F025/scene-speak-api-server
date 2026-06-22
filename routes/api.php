<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });
});

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::middleware('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });
});