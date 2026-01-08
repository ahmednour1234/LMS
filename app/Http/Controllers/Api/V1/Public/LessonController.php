<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Http\Resources\Api\V1\Public\LessonListResource;
use App\Http\Resources\Api\V1\Public\LessonResource;
use App\Http\Services\LessonService;
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
    protected LessonService $lessonService;

    public function __construct(LessonService $lessonService)
    {
        $this->lessonService = $lessonService;
    }

    /**
     * List Lessons
     * 
     * Get a paginated list of lessons with optional filtering and sorting.
     * 
     * @queryParam q string optional Search lessons by title (ar/en). Example: Introduction
     * @queryParam section_id integer optional Filter by section ID. Example: 1
     * @queryParam course_id integer optional Filter by course ID. Example: 1
     * @queryParam lesson_type string optional Filter by lesson type: recorded, live, mixed. Example: recorded
     * @queryParam active integer optional Filter by active status (1 for active, 0 for inactive). Default: 1. Example: 1
     * @queryParam is_preview integer optional Filter by preview status (1 for preview, 0 for non-preview). Example: 0
     * @queryParam sort string optional Sort order: newest, oldest, title, sort_order. Default: newest. Example: newest
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
        $filters = [
            'q' => $request->input('q'),
            'section_id' => $request->input('section_id'),
            'course_id' => $request->input('course_id'),
            'lesson_type' => $request->input('lesson_type'),
            'active' => $request->input('active', 1),
            'is_preview' => $request->input('is_preview'),
            'sort' => $request->input('sort', 'newest'),
        ];

        $perPage = (int) $request->input('per_page', 15);
        $lessons = $this->lessonService->getPaginated($filters, $perPage);

        return $this->paginatedResponse(
            LessonListResource::collection($lessons),
            'Lessons retrieved successfully.'
        );
    }

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

