<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

use App\Exceptions\ApiClientException;
use App\Http\Middleware\AuthenticateApiKey;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.key' => AuthenticateApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(
            function (
                ApiClientException $exception,
                Request $request
            ) {
                if (! $request->is('api/*')) {
                    return null;
                }

                return response()->json([
                    'message' => $exception->getMessage(),
                ], $exception->httpStatus);
            }
        );

        $exceptions->dontReport(
            ApiClientException::class
        );
    })->create();
