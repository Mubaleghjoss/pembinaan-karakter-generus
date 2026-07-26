<?php

use App\Exceptions\BusinessException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Set timezone to Indonesia (Asia/Jakarta) - UTC+7
// Force set timezone directly since env() may not be available during bootstrap
date_default_timezone_set('Asia/Jakarta');

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\RepositoryServiceProvider::class,
        \App\Providers\ServiceLayerServiceProvider::class,
        \App\Providers\RateLimitServiceProvider::class,
        \App\Providers\SettingsServiceProvider::class,
        \App\Providers\BladeServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role.permission' => \App\Http\Middleware\RolePermission::class,
            'sanitize' => \App\Http\Middleware\SanitizeInput::class,
            'auth.siswa' => \App\Http\Middleware\AuthenticateSiswa::class,
            'auth.ortu' => \App\Http\Middleware\AuthenticateOrtu::class,
            'pamong.permission' => \App\Http\Middleware\CheckPamongPermission::class,
            'log.pamong' => \App\Http\Middleware\LogPamongActivity::class,
            'admin.only' => \App\Http\Middleware\EnsureAdminUser::class,
            'guru.profile' => \App\Http\Middleware\EnsureTeacherPortalAccess::class,
            'guru.password' => \App\Http\Middleware\EnsureTeacherPasswordChanged::class,
        ]);

        // Apply sanitize middleware to all API routes
        $middleware->api(append: [
            \App\Http\Middleware\SanitizeInput::class,
        ]);

        // Update last_login_at once per day for authenticated web users
        $middleware->web(append: [
            \App\Http\Middleware\UpdateLastActivity::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\RestrictGuruToPortal::class,
        ]);
        
        // Exclude QR scan endpoint from CSRF verification (public endpoint)
        $middleware->validateCsrfTokens(except: [
            'qr/scan',
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Use respond() to handle all API exceptions in one place
        $exceptions->respond(function ($response, Throwable $e, Request $request) {
            // Only handle API requests
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return $response;
            }

            // Handle ValidationException
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            // Handle ModelNotFoundException
            if ($e instanceof ModelNotFoundException) {
                $modelName = class_basename($e->getModel());

                return response()->json([
                    'success' => false,
                    'error' => 'Not found',
                    'message' => "{$modelName} tidak ditemukan",
                    'code' => 'NOT_FOUND',
                ], 404);
            }

            // Handle NotFoundHttpException (for route not found)
            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'error' => 'Not found',
                    'message' => 'Resource tidak ditemukan',
                    'code' => 'NOT_FOUND',
                ], 404);
            }

            // Handle AuthenticationException
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'Autentikasi diperlukan',
                    'code' => 'UNAUTHORIZED',
                ], 401);
            }

            // Handle AccessDeniedHttpException
            if ($e instanceof AccessDeniedHttpException) {
                return response()->json([
                    'success' => false,
                    'error' => 'Forbidden',
                    'message' => 'Akses ditolak',
                    'code' => 'FORBIDDEN',
                ], 403);
            }

            // Handle BusinessException
            if ($e instanceof BusinessException) {
                $responseData = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'code' => $e->getErrorCode(),
                ];

                $additionalData = $e->getAdditionalData();
                if (! empty($additionalData)) {
                    $responseData = array_merge($responseData, $additionalData);
                }

                return response()->json($responseData, $e->getHttpStatus());
            }

            // Handle generic exceptions (production mode)
            if (app()->environment('production')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Server error',
                    'message' => 'Terjadi kesalahan pada server',
                    'code' => 'SERVER_ERROR',
                ], 500);
            }

            // Di development, tampilkan detail error
            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'message' => $e->getMessage(),
                'code' => 'SERVER_ERROR',
                'trace' => $e->getTrace(),
            ], 500);
        });
    })->create();
