<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Domain\Training\Models\Lesson;
use App\Domain\Enrollment\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Student Lessons
 *
 * Student API for accessing lesson details.
 */
class LessonController extends ApiController
{
    /**
     * List Lessons
     *
     * Get all lessons from courses the student is enrolled in with optional filtering.
     *
     * @queryParam course_id integer optional Filter by course ID. Example: 1
     * @queryParam title string optional Search lessons by title. Example: Introduction
     * @queryParam per_page integer optional Number of items per page. Default: 15. Example: 15
     * @queryParam page integer optional Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Lessons retrieved successfully.",
     *   "data": [...],
     *   "meta": {
     *     "pagination": {...}
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $student = auth('students')->user();

        $enrollments = Enrollment::where('student_id', $student->id)
            ->whereIn('status', ['active', 'pending', 'pending_payment'])
            ->pluck('course_id');

        if ($enrollments->isEmpty()) {
            return $this->paginatedResponse(
                collect([]),
                'Lessons retrieved successfully.'
            );
        }

        $query = Lesson::whereHas('section', function ($q) use ($enrollments, $request) {
            $q->whereIn('course_id', $enrollments);

            if ($request->has('course_id')) {
                $q->where('course_id', $request->input('course_id'));
            }
        })
        ->where('is_active', true)
        ->with(['section.course']);

        if ($request->has('title') && !empty($request->input('title'))) {
            $title = $request->input('title');
            $query->where(function ($q) use ($title) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar')) LIKE ?", ["%{$title}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$title}%"]);
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $lessons = $query->orderBy('sort_order')->orderBy('id')->paginate($perPage);

        return $this->paginatedResponse(
            \App\Http\Resources\V1\Student\LessonResource::collection($lessons),
            'Lessons retrieved successfully.'
        );
    }

    /**
     * Show Lesson
     *
     * Get lesson details with items (requires enrollment).
     *
     * @urlParam lesson integer required The ID of the lesson. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Lesson retrieved successfully.",
     *   "data": {
     *     "id": 1,
     *     "title": {...},
     *     "items": [...]
     *   }
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "You are not enrolled in this course.",
     *   "error": {
     *     "code": "FORBIDDEN"
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Lesson not found.",
     *   "error": {
     *     "code": "NOT_FOUND"
     *   }
     * }
     */
    public function show(int $lesson): JsonResponse
    {
        $student = auth('students')->user();
        $lessonModel = Lesson::with(['section.course', 'items.mediaFile'])
            ->find($lesson);

        if (!$lessonModel) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Lesson not found.',
                null,
                404
            );
        }

        $enrollment = \App\Domain\Enrollment\Models\Enrollment::where('student_id', $student->id)
            ->where('course_id', $lessonModel->section->course_id)
            ->whereIn('status', ['active', 'pending', 'pending_payment'])
            ->first();

        if (!$enrollment) {
            return $this->errorResponse(
                ApiErrorCode::FORBIDDEN,
                'You are not enrolled in this course.',
                null,
                403
            );
        }

        return $this->successResponse(
            new \App\Http\Resources\V1\Student\LessonResource($lessonModel),
            'Lesson retrieved successfully.'
        );
    }
}
