<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle UTF-8 encoding errors in JSON responses
        $exceptions->render(function (\InvalidArgumentException $e, $request) {
            if (str_contains($e->getMessage(), 'Malformed UTF-8')) {
                if ($request->expectsJson() || $request->is('livewire/*')) {
                    \Log::warning('UTF-8 encoding error', [
                        'message' => $e->getMessage(),
                        'url' => $request->fullUrl(),
                    ]);
                    
                    return response()->json([
                        'message' => 'An error occurred while processing your request. Please check your data for invalid characters.',
                    ], 500);
                }
            }
        });
    })->create();
