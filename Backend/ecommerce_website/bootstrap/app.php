<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Phase 1: Enable Session & Cookie persistence across API routes
        $middleware->api(append: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->alias([
            'admin'        => \App\Http\Middleware\AdminMiddleware::class,
            'role'         => \App\Http\Middleware\RoleMiddleware::class,
            'check.active' => \App\Http\Middleware\CheckActiveUser::class,
            'auth.api'     => \App\Http\Middleware\ApiAuthMiddleware::class,
        ]);
    })
    ->booted(function (): void {
        // Phase 2: Register Named Rate Limiter for Anti-Spam / Refresh Limiting (10 hits/min by IP)
        \Illuminate\Support\Facades\RateLimiter::for('anti-spam', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)
                ->by($request->ip())
                ->response(function (\Illuminate\Http\Request $request, array $headers) {
                    $retryAfter = (int) ($headers['Retry-After'] ?? 60);
                    $payload = [
                        'success' => false,
                        'message' => "Too many requests. Anti-spam protection triggered (maximum 10 hits per minute). Please wait {$retryAfter} seconds before trying again.",
                        'retry_after_seconds' => $retryAfter,
                    ];

                    if ($request->wantsJson() || $request->is('api/*')) {
                        return response()->json($payload, 429, $headers);
                    }

                    return response($payload['message'], 429, $headers);
                });
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
