<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Teacher\CourseController;

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

    // Public API (No Authentication Required)
    Route::prefix('public')->group(function () {
        // Teachers
        Route::get('/teachers', [App\Http\Controllers\Api\V1\Public\TeacherController::class, 'index']);
        Route::get('/teachers/{teacher}', [App\Http\Controllers\Api\V1\Public\TeacherController::class, 'show']);

        // Programs
        Route::get('/programs', [App\Http\Controllers\Api\V1\Public\ProgramController::class, 'index']);
        Route::get('/programs/{program}', [App\Http\Controllers\Api\V1\Public\ProgramController::class, 'show']);
        Route::get('/programs/{program}/courses', [App\Http\Controllers\Api\V1\Public\ProgramController::class, 'courses']);

        // Courses
        Route::get('/courses', [App\Http\Controllers\Api\V1\Public\CourseController::class, 'index']);
        Route::get('/courses/{course}', [App\Http\Controllers\Api\V1\Public\CourseController::class, 'show']);
        Route::get('/courses/{course}/prices', [App\Http\Controllers\Api\V1\Public\CourseController::class, 'prices']);

        // Lessons
        Route::get('/lessons', [App\Http\Controllers\Api\V1\Public\LessonController::class, 'index']);
        Route::get('/lessons/{lesson}', [App\Http\Controllers\Api\V1\Public\LessonController::class, 'show']);
    });
});

Route::prefix('teacher')->group(function () {
    Route::post('/register', [App\Http\Controllers\Api\Teacher\TeacherAuthController::class, 'register']);
    Route::post('/login', [App\Http\Controllers\Api\Teacher\TeacherAuthController::class, 'login']);
    Route::post('/forgot-password', [App\Http\Controllers\Api\Teacher\TeacherPasswordController::class, 'forgotPassword']);
    Route::post('/reset-password', [App\Http\Controllers\Api\Teacher\TeacherPasswordController::class, 'resetPassword']);

    Route::middleware('auth:teacher')->group(function () {
        Route::get('programs', [App\Http\Controllers\Api\Teacher\ProgramController::class, 'index']);
        Route::post('programs', [App\Http\Controllers\Api\Teacher\ProgramController::class, 'store']);
        Route::get('programs/{program}', [App\Http\Controllers\Api\Teacher\ProgramController::class, 'show']);
        Route::put('programs/{program}', [App\Http\Controllers\Api\Teacher\ProgramController::class, 'update']);
        Route::patch('programs/{program}/toggle-active', [App\Http\Controllers\Api\Teacher\ProgramController::class, 'toggleActive']);
        Route::get('courses', [CourseController::class, 'index']);
        Route::post('courses', [CourseController::class, 'store']);
        Route::get('courses/{course}', [CourseController::class, 'show']);
        Route::put('courses/{course}', [CourseController::class, 'update']);
        Route::patch('courses/{course}/toggle-active', [CourseController::class, 'toggleActive']);
        Route::get('courses/{course}/price', [CourseController::class, 'price']);
        Route::post('/logout', [App\Http\Controllers\Api\Teacher\TeacherAuthController::class, 'logout']);
        Route::get('/me', [App\Http\Controllers\Api\Teacher\TeacherAuthController::class, 'me']);
        Route::post('/refresh', [App\Http\Controllers\Api\Teacher\TeacherAuthController::class, 'refresh']);
        Route::put('/profile', [App\Http\Controllers\Api\Teacher\TeacherAuthController::class, 'updateProfile']);
    });
});

