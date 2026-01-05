<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Http\Resources\Api\V1\Public\LessonResource;
use App\Domain\Training\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Public Lessons
 * 
 * Public API for viewing lesson details. No authentication required.
 */
class LessonController extends ApiController
{
    /**
     * Show Lesson
     * 
     * Get a single lesson by ID with section and course references.
     * 
     * @urlParam lesson integer required The ID of the lesson. Example: 1
     * @queryParam include string optional Comma-separated list of relations to include. Example: items
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Lesson retrieved successfully.",
     *   "data": {
     *     "id": 1,
     *     "title": {"ar": "...", "en": "..."},
     *     "description": {"ar": "...", "en": "..."},
     *     "type": "recorded",
     *     "is_preview": false,
     *     "estimated_minutes": 60,
     *     "published_at": "2026-01-15T12:00:00+00:00",
     *     "sort_order": 1,
     *     "active": true,
     *     "section_id": 1,
     *     "section": {
     *       "id": 1,
     *       "title": {...},
     *       "sort_order": 1
     *     },
     *     "course": {
     *       "id": 1,
     *       "title": {...},
     *       "code": "COURSE001"
     *     }
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "The requested resource was not found.",
     *   "error": {
     *     "code": "NOT_FOUND"
     *   }
     * }
     */
    public function show(Request $request, int $lesson): JsonResponse
    {
        $query = Lesson::query();

        // Load section and course relations
        $query->with(['section.course']);

        // Handle include parameter (for future lesson_items)
        $include = $request->input('include');
        if ($include) {
            $includes = explode(',', $include);
            $includes = array_map('trim', $includes);
            
            if (in_array('items', $includes)) {
                $query->with('items');
            }
        }

        $lessonModel = $query->find($lesson);

        if (!$lessonModel) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Lesson not found.',
                null,
                404
            );
        }

        return $this->successResponse(
            new LessonResource($lessonModel),
            'Lesson retrieved successfully.'
        );
    }
}

