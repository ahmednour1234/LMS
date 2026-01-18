<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Domain\Training\Models\Lesson;
use Illuminate\Http\JsonResponse;

/**
 * @group Student Lessons
 *
 * Student API for accessing lesson details.
 */
class LessonController extends ApiController
{
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
