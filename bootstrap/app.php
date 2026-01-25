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
        // Global middleware for all requests
        $middleware->use([
            \App\Http\Middleware\AddSecurityHeaders::class,
        ]);

        // API middleware group
        $middleware->api([
            \App\Http\Middleware\ValidateInternalRequest::class,
            \App\Http\Middleware\AuthorizeCatalogAccess::class,
        ]);

        // Custom middleware aliases
        $middleware->alias([
            'api.response' => \App\Http\Middleware\ApiResponseMiddleware::class,
            'api.logging' => \App\Http\Middleware\ApiLoggingMiddleware::class,
            'api.rate.limit' => \App\Http\Middleware\ApiRateLimitMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
