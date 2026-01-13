<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Http\Resources\Public\CourseListResource;
use App\Http\Resources\Public\ProgramResource;
use App\Http\Services\ProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Public Programs
 *
 * Public API for browsing programs. No authentication required.
 */
class ProgramController extends ApiController
{
    protected ProgramService $programService;

    public function __construct(ProgramService $programService)
    {
        $this->programService = $programService;
    }

    /**
     * List Programs
     *
     * Get a paginated list of programs with optional filtering and sorting.
     *
     * @queryParam q string optional Search programs by name (ar/en). Example: Programming
     * @queryParam active integer optional Filter by active status (1 for active, 0 for inactive). Default: 1. Example: 1
     * @queryParam sort string optional Sort order: newest, oldest, or name. Default: newest. Example: newest
     * @queryParam per_page integer optional Number of items per page. Default: 15. Example: 15
     * @queryParam page integer optional Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Programs retrieved successfully.",
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
            'active' => $request->input('active', 1),
            'sort' => $request->input('sort', 'newest'),
        ];

        $perPage = (int) $request->input('per_page', 15);
        $programs = $this->programService->getPaginated($filters, $perPage);

        return $this->paginatedResponse(
            ProgramResource::collection($programs),
            'Programs retrieved successfully.'
        );
    }

    /**
     * Show Program
     *
     * Get a single program by ID with courses.
     *
     * @urlParam program integer required The ID of the program. Example: 1
     * @queryParam include_courses integer optional Include courses in response (1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Program retrieved successfully.",
     *   "data": {
     *     "id": 1,
     *     "name": {"ar": "...", "en": "..."},
     *     "description": {"ar": "...", "en": "..."},
     *     "active": true,
     *     "code": "PROG001",
     *     "courses": [...],
     *     "created_at": "2026-01-15T12:00:00+00:00"
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
    public function show(Request $request, int $program): JsonResponse
    {
        $includeCourses = $request->input('include_courses', 1) == 1;
        $programModel = $this->programService->findById($program, $includeCourses);

        if (!$programModel) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Program not found.',
                null,
                404
            );
        }

        return $this->successResponse(
            new ProgramResource($programModel),
            'Program retrieved successfully.'
        );
    }

    /**
     * Get Program Courses
     *
     * Get courses for a specific program with optional filtering.
     *
     * @urlParam program integer required The ID of the program. Example: 1
     * @queryParam q string optional Search courses by title (ar/en). Example: PHP
     * @queryParam active integer optional Filter by active status (1 for active, 0 for inactive). Default: 1. Example: 1
     * @queryParam delivery_type string optional Filter by delivery type: online, onsite, hybrid. Example: online
     * @queryParam owner_teacher_id integer optional Filter by owner teacher ID. Example: 1
     * @queryParam teacher_id integer optional Filter by teacher ID (owner or assigned). Example: 1
     * @queryParam has_price integer optional Only show courses with active prices (1). Example: 1
     * @queryParam sort string optional Sort order: newest, oldest, or title. Default: newest. Example: newest
     * @queryParam per_page integer optional Number of items per page. Default: 15. Example: 15
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Program courses retrieved successfully.",
     *   "data": [...],
     *   "meta": {
     *     "pagination": {...}
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
    public function courses(Request $request, int $program): JsonResponse
    {
        // Verify program exists
        $programModel = $this->programService->findById($program);
        if (!$programModel) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Program not found.',
                null,
                404
            );
        }

        $filters = [
            'q' => $request->input('q'),
            'active' => $request->input('active', 1),
            'delivery_type' => $request->input('delivery_type'),
            'owner_teacher_id' => $request->input('owner_teacher_id'),
            'teacher_id' => $request->input('teacher_id'),
            'has_price' => $request->input('has_price'),
            'sort' => $request->input('sort', 'newest'),
        ];

        $perPage = (int) $request->input('per_page', 15);
        $courses = $this->programService->getProgramCourses($program, $filters, $perPage);

        return $this->paginatedResponse(
            CourseListResource::collection($courses),
            'Program courses retrieved successfully.'
        );
    }
}

