<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All API routes are grouped under "api" middleware by RouteServiceProvider.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Examples (DEV)
    |--------------------------------------------------------------------------
    */
    Route::prefix('example')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\ExampleController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\ExampleController::class, 'store']);
        Route::get('/paginated', [App\Http\Controllers\Api\ExampleController::class, 'paginated']);
        Route::get('/errors', [App\Http\Controllers\Api\ExampleController::class, 'errorExamples']);
    });

    /*
    |--------------------------------------------------------------------------
    | Student Auth (Public + Protected)
    |--------------------------------------------------------------------------
    */
    Route::prefix('student')->group(function () {

        // Public
        Route::post('/register', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'register']);
        Route::post('/verify-otp', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'verifyOtp']);
        Route::post('/login', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'login']);
        Route::post('/forgot-password', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'resetPassword']);

        // Protected
        Route::middleware('auth:students')->group(function () {
            Route::get('/me', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'me']);
            Route::post('/logout', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'logout']);
            Route::post('/refresh', [App\Http\Controllers\Api\V1\Student\AuthController::class, 'refresh']);
            Route::put('/profile', [App\Http\Controllers\Api\V1\Student\ProfileController::class, 'update']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Branches (Public Read-only)
    |--------------------------------------------------------------------------
    */
    Route::get('/branches', [App\Http\Controllers\Api\V1\BranchController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Public (No Auth)
    |--------------------------------------------------------------------------
    */
    Route::prefix('public')->group(function () {

        // Teachers
        Route::prefix('teachers')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\Public\TeacherController::class, 'index']);
            Route::get('/{teacher}', [App\Http\Controllers\Api\V1\Public\TeacherController::class, 'show']);
        });

        // Programs
        Route::prefix('programs')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\Public\ProgramController::class, 'index']);
            Route::get('/{program}', [App\Http\Controllers\Api\V1\Public\ProgramController::class, 'show']);
            Route::get('/{program}/courses', [App\Http\Controllers\Api\V1\Public\ProgramController::class, 'courses']);
        });

        // Courses
        Route::prefix('courses')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\Public\CourseController::class, 'index']);
            Route::get('/{course}', [App\Http\Controllers\Api\V1\Public\CourseController::class, 'show']);
            Route::get('/{course}/prices', [App\Http\Controllers\Api\V1\Public\CourseController::class, 'prices']);
        });

        // Lessons
        Route::prefix('lessons')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\Public\LessonController::class, 'index']);
            Route::get('/{lesson}', [App\Http\Controllers\Api\V1\Public\LessonController::class, 'show']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Teacher (Auth + Protected)
    |--------------------------------------------------------------------------
    | NOTE: You had "teacher" outside v1, we unify it here under /v1/teacher
    |--------------------------------------------------------------------------
    */
    Route::prefix('teacher')->group(function () {

        // Teacher Auth (Public)
        Route::prefix('auth')->group(function () {
            Route::post('/register', [App\Http\Controllers\Api\V1\Teacher\TeacherAuthController::class, 'register']);
            Route::post('/login', [App\Http\Controllers\Api\V1\Teacher\TeacherAuthController::class, 'login']);
            Route::post('/forgot-password', [App\Http\Controllers\Api\V1\Teacher\TeacherPasswordController::class, 'forgotPassword']);
            Route::post('/reset-password', [App\Http\Controllers\Api\V1\Teacher\TeacherPasswordController::class, 'resetPassword']);
        });

        // Teacher Protected (JWT)
        Route::middleware('auth:teacher')->group(function () {

            // Teacher Session / Profile
            Route::prefix('auth')->group(function () {
                Route::post('/logout', [App\Http\Controllers\Api\V1\Teacher\TeacherAuthController::class, 'logout']);
                Route::get('/me', [App\Http\Controllers\Api\V1\Teacher\TeacherAuthController::class, 'me']);
                Route::post('/refresh', [App\Http\Controllers\Api\V1\Teacher\TeacherAuthController::class, 'refresh']);
                Route::put('/profile', [App\Http\Controllers\Api\V1\Teacher\TeacherAuthController::class, 'updateProfile']);
            });

            // Programs
            Route::prefix('programs')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\V1\Teacher\ProgramController::class, 'index']);
                Route::post('/', [App\Http\Controllers\Api\V1\Teacher\ProgramController::class, 'store']);
                Route::get('/{program}', [App\Http\Controllers\Api\V1\Teacher\ProgramController::class, 'show']);
                Route::put('/{program}', [App\Http\Controllers\Api\V1\Teacher\ProgramController::class, 'update']);
                Route::patch('/{program}/toggle-active', [App\Http\Controllers\Api\V1\Teacher\ProgramController::class, 'toggleActive']);
            });

            // Courses (Teacher CRUD)
            Route::prefix('courses')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\V1\Teacher\CourseController::class, 'index']);
                Route::post('/', [App\Http\Controllers\Api\V1\Teacher\CourseController::class, 'store']);
                Route::get('/me', [App\Http\Controllers\Api\V1\Teacher\TeacherCourseMeController::class, 'index']);
                Route::get('/{course}', [App\Http\Controllers\Api\V1\Teacher\CourseController::class, 'show']);
                Route::put('/{course}', [App\Http\Controllers\Api\V1\Teacher\CourseController::class, 'update']);
                Route::patch('/{course}/toggle-active', [App\Http\Controllers\Api\V1\Teacher\CourseController::class, 'toggleActive']);
                Route::get('/{course}/price', [App\Http\Controllers\Api\V1\Teacher\CourseController::class, 'price']);
            });

            // Media Upload
            Route::post('media', [App\Http\Controllers\Api\V1\Teacher\TeacherMediaController::class, 'store']);

            // Sections
            Route::prefix('sections')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\V1\Teacher\CourseSectionController::class, 'index']);
                Route::post('/', [App\Http\Controllers\Api\V1\Teacher\CourseSectionController::class, 'store']);
                Route::get('/{section}', [App\Http\Controllers\Api\V1\Teacher\CourseSectionController::class, 'show']);
                Route::put('/{section}', [App\Http\Controllers\Api\V1\Teacher\CourseSectionController::class, 'update']);
                Route::patch('/{section}/active', [App\Http\Controllers\Api\V1\Teacher\CourseSectionController::class, 'toggleActive']);
            });

            // Lessons
            Route::prefix('lessons')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\V1\Teacher\LessonController::class, 'index']);
                Route::post('/', [App\Http\Controllers\Api\V1\Teacher\LessonController::class, 'store']);
                Route::get('/{lesson}', [App\Http\Controllers\Api\V1\Teacher\LessonController::class, 'show']);
                Route::put('/{lesson}', [App\Http\Controllers\Api\V1\Teacher\LessonController::class, 'update']);
                Route::patch('/{lesson}/active', [App\Http\Controllers\Api\V1\Teacher\LessonController::class, 'toggleActive']);
            });

            // Lesson Items
            Route::prefix('lesson-items')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\V1\Teacher\LessonItemController::class, 'index']);
                Route::post('/', [App\Http\Controllers\Api\V1\Teacher\LessonItemController::class, 'store']);
                Route::get('/{item}', [App\Http\Controllers\Api\V1\Teacher\LessonItemController::class, 'show']);
                Route::put('/{item}', [App\Http\Controllers\Api\V1\Teacher\LessonItemController::class, 'update']);
                Route::patch('/{item}/active', [App\Http\Controllers\Api\V1\Teacher\LessonItemController::class, 'toggleActive']);
            });

            // Exams
            Route::prefix('exams')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\V1\Teacher\ExamController::class, 'index']);
                Route::post('/', [App\Http\Controllers\Api\V1\Teacher\ExamController::class, 'store']);
                Route::get('/{exam}', [App\Http\Controllers\Api\V1\Teacher\ExamController::class, 'show']);
                Route::put('/{exam}', [App\Http\Controllers\Api\V1\Teacher\ExamController::class, 'update']);
                Route::patch('/{exam}/active', [App\Http\Controllers\Api\V1\Teacher\ExamController::class, 'toggleActive']);
            });

            // Exam Questions
            Route::prefix('exam-questions')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\V1\Teacher\ExamQuestionController::class, 'index']);
                Route::post('/', [App\Http\Controllers\Api\V1\Teacher\ExamQuestionController::class, 'store']); // single OR bulk
                Route::put('/{question}', [App\Http\Controllers\Api\V1\Teacher\ExamQuestionController::class, 'update']);
                Route::delete('/{question}', [App\Http\Controllers\Api\V1\Teacher\ExamQuestionController::class, 'destroy']);
            });
        });
    });
});

/*
|--------------------------------------------------------------------------
| Scribe Notes (How to generate docs)
|--------------------------------------------------------------------------
| 1) Install (if not installed):
|    composer require --dev knuckleswtf/scribe
|
| 2) Publish config:
|    php artisan vendor:publish --tag=scribe-config
|
| 3) Add this to config/scribe.php (recommended):
|    'routes' => [
|        [
|            'match' => [
|                'prefixes' => ['api/v1'],
|            ],
|            'include' => [
|                'api/v1/*',
|            ],
|        ],
|    ],
|    'auth' => [
|        'enabled' => true,
|        'default' => 'bearer',
|        'in' => 'header',
|        'name' => 'Authorization',
|        'use_value' => 'Bearer {YOUR_TOKEN}',
|    ],
|
| 4) Generate:
|    php artisan scribe:generate
|--------------------------------------------------------------------------
*/
