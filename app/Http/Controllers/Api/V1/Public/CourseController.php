<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Http\Resources\Api\V1\Public\CourseListResource;
use App\Http\Resources\Api\V1\Public\CoursePriceResource;
use App\Http\Resources\Api\V1\Public\CourseShowResource;
use App\Http\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Public Courses
 *
 * Public API for browsing courses. No authentication required.
 */
class CourseController extends ApiController
{
    protected CourseService $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    /**
     * List Courses
     *
     * Get a paginated list of courses with optional filtering and sorting.
     *
     * @queryParam q string optional Search courses by title (ar/en). Example: PHP
     * @queryParam program_id integer optional Filter by program ID. Example: 1
     * @queryParam delivery_type string optional Filter by delivery type: online, onsite, hybrid. Example: online
     * @queryParam owner_teacher_id integer optional Filter by owner teacher ID. Example: 1
     * @queryParam teacher_id integer optional Filter by teacher ID (owner or assigned). Example: 1
     * @queryParam active integer optional Filter by active status (1 for active, 0 for inactive). Default: 1. Example: 1
     * @queryParam has_price integer optional Only show courses with active prices (1). Example: 1
     * @queryParam sort string optional Sort order: newest, oldest, or title. Default: newest. Example: newest
     * @queryParam per_page integer optional Number of items per page. Default: 15. Example: 15
     * @queryParam page integer optional Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Courses retrieved successfully.",
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
            'program_id' => $request->input('program_id'),
            'delivery_type' => $request->input('delivery_type'),
            'owner_teacher_id' => $request->input('owner_teacher_id'),
            'teacher_id' => $request->input('teacher_id'),
            'active' => $request->input('active', 1),
            'has_price' => $request->input('has_price'),
            'sort' => $request->input('sort', 'newest'),
        ];

        $perPage = (int) $request->input('per_page', 15);
        $courses = $this->courseService->getPaginated($filters, $perPage);

        return $this->paginatedResponse(
            CourseListResource::collection($courses),
            'Courses retrieved successfully.'
        );
    }

    /**
     * Show Course
     *
     * Get a single course by ID with sections and lessons.
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course retrieved successfully.",
     *   "data": {
     *     "id": 1,
     *     "title": {"ar": "...", "en": "..."},
     *     "description": {"ar": "...", "en": "..."},
     *     "active": true,
     *     "program_id": 1,
     *     "delivery_type": "online",
     *     "sections": [...]
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
    public function show(int $course): JsonResponse
    {
        $courseModel = $this->courseService->findByIdWithContent($course);

        if (!$courseModel) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Course not found.',
                null,
                404
            );
        }

        return $this->successResponse(
            new CourseShowResource($courseModel),
            'Course retrieved successfully.'
        );
    }

    /**
     * Get Course Prices
     *
     * Get price list for a specific course with optional filtering.
     * Prices are ordered by: exact match branch_id first, then null (global);
     * same logic for delivery_type.
     *
     * @urlParam course integer required The ID of the course. Example: 1
     * @queryParam branch_id integer optional Filter by branch ID. Example: 1
     * @queryParam delivery_type string optional Filter by delivery type: online, onsite, hybrid. Example: online
     * @queryParam active integer optional Filter by active status (1 for active, 0 for inactive). Default: 1. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course prices retrieved successfully.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "course_id": 1,
     *       "branch_id": 1,
     *       "delivery_type": "online",
     *       "price": 1000.00,
     *       "allow_installments": true,
     *       "min_down_payment": 200.00,
     *       "max_installments": 5,
     *       "active": true
     *     }
     *   ]
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "The requested resource was not found.",
     *   "error": {
     *     "code": "NOT_FOUND"
     *   }
     * }
     */
    public function prices(Request $request, int $course): JsonResponse
    {
        // Verify course exists
        $courseModel = $this->courseService->findById($course);
        if (!$courseModel) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Course not found.',
                null,
                404
            );
        }

        $filters = [
            'branch_id' => $request->input('branch_id'),
            'delivery_type' => $request->input('delivery_type'),
            'active' => $request->input('active', 1),
        ];

        $prices = $this->courseService->getCoursePrices($course, $filters);

        return $this->successResponse(
            CoursePriceResource::collection($prices),
            'Course prices retrieved successfully.'
        );
    }
}

