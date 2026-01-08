<?php

use App\Exceptions\BusinessException;
use App\Http\Enums\ApiErrorCode;
use App\Http\Services\ApiResponseService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apply SetLocale middleware to all routes
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        
        $middleware->api(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle UTF-8 encoding errors in JSON responses
        $exceptions->render(function (\InvalidArgumentException $e, Request $request) {
            if (str_contains($e->getMessage(), 'Malformed UTF-8')) {
                if ($request->expectsJson() || $request->is('livewire/*')) {
                    \Log::warning('UTF-8 encoding error', [
                        'message' => $e->getMessage(),
                        'url' => $request->fullUrl(),
                    ]);
                    
                    return ApiResponseService::error(
                        ApiErrorCode::INTERNAL_ERROR,
                        'An error occurred while processing your request. Please check your data for invalid characters.',
                        null,
                        500
                    );
                }
            }
        });

        // Handle ValidationException (422)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponseService::error(
                    ApiErrorCode::VALIDATION_ERROR,
                    'The provided data is invalid.',
                    $e->errors(),
                    422
                );
            }
        });

        // Handle AuthenticationException (401)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponseService::error(
                    ApiErrorCode::UNAUTHORIZED,
                    $e->getMessage() ?: 'Authentication required.',
                    null,
                    401
                );
            }
        });

        // Handle AuthorizationException (403)
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponseService::error(
                    ApiErrorCode::FORBIDDEN,
                    $e->getMessage() ?: 'You do not have permission to perform this action.',
                    null,
                    403
                );
            }
        });

        // Handle ModelNotFoundException (404)
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $model = class_basename($e->getModel());
                return ApiResponseService::error(
                    ApiErrorCode::NOT_FOUND,
                    "The requested {$model} was not found.",
                    null,
                    404
                );
            }
        });

        // Handle BusinessException (custom domain errors)
        $exceptions->render(function (BusinessException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponseService::error(
                    $e->getErrorCode(),
                    $e->getMessage(),
                    $e->getDetails(),
                    $e->getCode() ?: 400
                );
            }
        });

        // Handle QueryException (database conflicts, etc.)
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // Check for unique constraint violations or conflicts
                $errorCode = $e->getCode();
                if ($errorCode === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                    return ApiResponseService::error(
                        ApiErrorCode::CONFLICT,
                        'A conflict occurred while processing your request. The resource may already exist.',
                        app()->environment(['local', 'staging']) ? ['sql_error' => $e->getMessage()] : null,
                        409
                    );
                }

                // Other database errors
                return ApiResponseService::error(
                    ApiErrorCode::INTERNAL_ERROR,
                    'A database error occurred.',
                    app()->environment(['local', 'staging']) ? ['sql_error' => $e->getMessage()] : null,
                    500
                );
            }
        });

        // Handle all other exceptions (500)
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // Log the exception
                \Log::error('Unhandled API exception', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Only expose details in non-production environments
                $details = app()->environment(['local', 'staging'])
                    ? [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
                    : null;

                return ApiResponseService::error(
                    ApiErrorCode::INTERNAL_ERROR,
                    'An internal server error occurred.',
                    $details,
                    500
                );
            }
        });
    })->create();
