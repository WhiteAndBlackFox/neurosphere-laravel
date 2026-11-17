<?php

declare(strict_types=1);

use App\Exceptions\QuotaExceededException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // This is a stateless JSON API with no "login" web route to redirect
        // guests to; without this, unauthenticated requests that don't send
        // `Accept: application/json` 500 on RouteNotFoundException instead
        // of returning a 401.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn () => true,
        );

        $exceptions->render(function (QuotaExceededException $e, Request $request): JsonResponse {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => ['code' => 'QUOTA_EXCEEDED'],
            ], 422);
        });
    })->create();
