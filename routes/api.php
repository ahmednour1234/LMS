<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Example API routes - replace with your actual API endpoints
Route::prefix('v1')->group(function () {
    Route::get('/example', [App\Http\Controllers\Api\ExampleController::class, 'index']);
    Route::post('/example', [App\Http\Controllers\Api\ExampleController::class, 'store']);
    Route::get('/example/paginated', [App\Http\Controllers\Api\ExampleController::class, 'paginated']);
    Route::get('/example/errors', [App\Http\Controllers\Api\ExampleController::class, 'errorExamples']);

    // Student Authentication Routes (Public)
    Route::prefix('student')->group(function () {
        Route::post('/register', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'register']);
        Route::post('/verify-otp', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'verifyOtp']);
        Route::post('/login', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'login']);
        Route::post('/forgot-password', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'resetPassword']);

        // Protected Routes (Require JWT Authentication)
        Route::middleware('auth:students')->group(function () {
            Route::get('/me', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'me']);
            Route::post('/logout', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'logout']);
            Route::post('/refresh', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'refresh']);
            Route::put('/profile', [App\Http\Controllers\Api\V1\Student\ProfileController::class, 'update']);
        });
    });

    // Branches API (Public, Read-only)
    Route::get('/branches', [App\Http\Controllers\Api\V1\BranchController::class, 'index']);
});

