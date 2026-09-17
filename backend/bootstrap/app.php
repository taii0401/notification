<?php

use App\Exceptions\ApiClientException;
use App\Http\Middleware\AuthenticateApiKey;
use App\Support\SystemCode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            function (Throwable $exception, Request $request) {
                if (! $request->is('api/*')) {
                    return null;
                }

                $systemCode = match (true) {
                    $exception instanceof ApiClientException => $exception->systemCode,
                    $exception instanceof ValidationException => SystemCode::VALIDATION_FAILED,
                    $exception instanceof NotFoundHttpException => SystemCode::RESOURCE_NOT_FOUND,
                    $exception instanceof MethodNotAllowedHttpException => SystemCode::METHOD_NOT_ALLOWED,
                    default => null,
                };

                if ($systemCode === null) {
                    return null;
                }

                $definition = SystemCode::definition($systemCode);

                $body = [
                    'message' => $definition['message'],
                ];

                if (
                    $exception instanceof ValidationException
                    || $exception instanceof ApiClientException
                ) {
                    $body['errors'] = $exception->errors();
                }

                return response()->json(
                    $body,
                    $definition['http_status']
                );
            }
        );

        $exceptions->dontReport(
            ApiClientException::class
        );
    })->create();
