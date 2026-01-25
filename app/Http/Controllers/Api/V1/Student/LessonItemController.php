<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Domain\Training\Models\LessonItem;
use App\Domain\Enrollment\Models\Enrollment;
use Illuminate\Http\JsonResponse;

class LessonItemController extends ApiController
{
    /**
     * Show Lesson Item
     *
     * Get lesson item details (requires enrollment in active course).
     *
     * @urlParam item integer required The ID of the lesson item. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Lesson item retrieved successfully.",
     *   "data": {
     *     "id": 1,
     *     "type": "video",
     *     "title": "Introduction Video",
     *     "external_url": null,
     *     "order": 1,
     *     "media_file": {
     *       "id": 1,
     *       "url": "...",
     *       "type": "video/mp4"
     *     }
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
     *   "message": "Lesson item not found.",
     *   "error": {
     *     "code": "NOT_FOUND"
     *   }
     * }
     */
    public function show(int $item): JsonResponse
    {
        $student = auth('students')->user();
        
        $lessonItem = LessonItem::with(['lesson.section.course', 'mediaFile'])
            ->where('is_active', true)
            ->whereHas('lesson', function ($query) {
                $query->where('is_active', true);
            })
            ->whereHas('lesson.section.course', function ($query) {
                $query->where('is_active', true);
            })
            ->find($item);

        if (!$lessonItem) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Lesson item not found.',
                null,
                404
            );
        }

        $course = $lessonItem->lesson->section->course;

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
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
            new \App\Http\Resources\V1\Student\LessonItemResource($lessonItem),
            'Lesson item retrieved successfully.'
        );
    }
}
