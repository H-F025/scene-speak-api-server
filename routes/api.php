<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnglishLevelController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\LearningSessionController;

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
        Route::patch('me/english-level', [EnglishLevelController::class, 'update']); // 追加

        Route::get('themes', [ThemeController::class, 'index']); // ← 追加

        Route::get('themes/{theme_level_id}/questions', [QuestionController::class, 'index']);

        Route::post('learning-sessions', [LearningSessionController::class, 'store']);
        Route::post('learning-sessions/{learning_session_id}/heartbeat', [LearningSessionController::class, 'heartbeat']);
        Route::get('learning-sessions/{learning_session_id}/questions/{question_id}', [QuestionController::class, 'show']);
        Route::post('learning-sessions/{learning_session_id}/questions/{question_id}/answer', [QuestionController::class, 'answer']);
    });
});