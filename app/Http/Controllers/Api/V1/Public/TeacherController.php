<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Http\Resources\Api\V1\Public\TeacherResource;
use App\Http\Services\TeacherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Public Teachers
 * 
 * Public API for browsing teachers. No authentication required.
 */
class TeacherController extends ApiController
{
    protected TeacherService $teacherService;

    public function __construct(TeacherService $teacherService)
    {
        $this->teacherService = $teacherService;
    }

    /**
     * List Teachers
     * 
     * Get a paginated list of teachers with optional filtering and sorting.
     * 
     * @queryParam q string optional Search teachers by name or email. Example: John
     * @queryParam active integer optional Filter by active status (1 for active, 0 for inactive). Default: 1. Example: 1
     * @queryParam sex string optional Filter by sex. Example: male
     * @queryParam has_courses integer optional Only show teachers with courses (1). Example: 1
     * @queryParam sort string optional Sort order: newest, oldest, name, or email. Default: newest. Example: newest
     * @queryParam per_page integer optional Number of items per page. Default: 15. Example: 15
     * @queryParam page integer optional Page number. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Teachers retrieved successfully.",
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
            'sex' => $request->input('sex'),
            'has_courses' => $request->input('has_courses'),
            'sort' => $request->input('sort', 'newest'),
        ];

        $perPage = (int) $request->input('per_page', 15);
        $teachers = $this->teacherService->getPaginated($filters, $perPage);

        return $this->paginatedResponse(
            TeacherResource::collection($teachers),
            'Teachers retrieved successfully.'
        );
    }

    /**
     * Show Teacher
     * 
     * Get a single teacher by ID with courses.
     * 
     * @urlParam teacher integer required The ID of the teacher. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Teacher retrieved successfully.",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "sex": "male",
     *     "photo": null,
     *     "active": true,
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
    public function show(int $teacher): JsonResponse
    {
        $teacherModel = $this->teacherService->findById($teacher);

        if (!$teacherModel) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Teacher not found.',
                null,
                404
            );
        }

        return $this->successResponse(
            new TeacherResource($teacherModel),
            'Teacher retrieved successfully.'
        );
    }
}

