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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => $e->getMessage(),
                    'details' => $e->errors(),
                ],
            ], 422);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();
            $code = match (true) {
                $status === 401 => 'UNAUTHENTICATED',
                $status === 403 => 'FORBIDDEN',
                $status === 404 => 'NOT_FOUND',
                $status === 409 => 'CONFLICT',
                $status === 429 => 'TOO_MANY_REQUESTS',
                default => 'ERROR',
            };

            return response()->json([
                'error' => ['code' => $code, 'message' => $e->getMessage() ?: 'Request failed.'],
            ], $status);
        });
    })->create();
