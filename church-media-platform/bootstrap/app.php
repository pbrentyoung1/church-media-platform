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
        // Temporarily disabled: 
        // $middleware->api(prepend: [
        //     \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        // ]);
        
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            // Temporarily disabled:
            // 'ability' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            // 'abilities' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'tenant.context' => \App\Http\Middleware\TenantContext::class,
            'tenant.access' => \App\Http\Middleware\RequiresTenantAccess::class,
            'requires.2fa' => \App\Http\Middleware\Requires2FA::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->booting(function () {
        // Rate limiters for Fortify
        \Illuminate\Support\Facades\RateLimiter::for('login', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)
                ->by($request->email . $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('two-factor', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)
                ->by($request->ip());
        });
    })
    ->create();
