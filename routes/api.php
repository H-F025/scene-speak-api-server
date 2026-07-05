<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnglishLevelController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
        });

    Route::get('english-levels', [EnglishLevelController::class, 'index']);
        Route::get('english-levels', [EnglishLevelController::class, 'index']);
            Route::patch('me/english-level', [EnglishLevelController::class, 'update']); // 追加
    });
});