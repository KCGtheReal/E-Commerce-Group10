<?php

declare(strict_types=1);

use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\SetApiContext;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->api(append: [
            SetApiContext::class,
        ]);
        $middleware->alias([
            'auth.api' => AuthenticateApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'code' => 'VALIDATION_ERROR',
                    'errors' => $e->errors(),
                ], $e->status);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Unauthenticated.',
                    'code' => 'UNAUTHENTICATED',
                ], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Forbidden.',
                    'code' => 'FORBIDDEN',
                ], 403);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                    'code' => 'NOT_FOUND',
                ], 404);
            }

            if ($e instanceof ThrottleRequestsException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please slow down.',
                    'code' => 'RATE_LIMITED',
                ], 429, $e->getHeaders());
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $code = match ($status) {
                    401 => 'UNAUTHENTICATED',
                    403 => 'FORBIDDEN',
                    404 => 'NOT_FOUND',
                    422 => 'VALIDATION_ERROR',
                    429 => 'RATE_LIMITED',
                    default => 'SERVER_ERROR',
                };

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Request error.',
                    'code' => $code,
                ], $status, $e->getHeaders());
            }

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error.',
                'code' => 'SERVER_ERROR',
            ], 500);
        });
    })->create();
