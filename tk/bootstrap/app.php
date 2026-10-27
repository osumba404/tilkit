<?php

use App\Support\Http\FriendlyHttpErrors;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'tilkit.platform' => \App\Http\Middleware\EnsureTilkitPlatformTool::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/tilkit/mobile/login',
            'api/tilkit/sightings',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            return FriendlyHttpErrors::renderValidation($exception, $request);
        });

        $exceptions->render(function (QueryException $exception, Request $request) {
            return FriendlyHttpErrors::renderQueryException($exception, $request);
        });
    })->create();
