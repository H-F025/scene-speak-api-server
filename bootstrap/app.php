<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json(['message' => '認証が必要です。'], 401);
        });

        // throttle ミドルウェアの既定メッセージは英語のため、フロントで表示できるよう日本語に差し替える
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            return response()->json([
                'message' => '送信回数の上限に達しました。しばらく時間をおいてからお試しください。',
            ], 429);
        });
    })->create();