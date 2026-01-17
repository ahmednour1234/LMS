<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Http\Requests\Teacher\StoreSessionRequest;
use App\Http\Requests\Teacher\UpdateSessionRequest;
use App\Http\Resources\Api\V1\Public\SessionResource;
use App\Http\Services\TeacherSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Teacher Sessions
 *
 * APIs for teachers to manage their own sessions.
 */
class SessionController extends ApiController
{
    public function __construct(
        protected TeacherSessionService $teacherSessionService
    ) {}

    /**
     * My Sessions (Paginated)
     *
     * @queryParam q string Search by title ar/en. Example: Intro
     * @queryParam course_id int Filter by course_id (must be teacher sessions). Example: 10
     * @queryParam lesson_id int Filter by lesson_id. Example: 5
     * @queryParam status string Filter by status (scheduled, completed, cancelled). Example: scheduled
     * @queryParam location_type string Filter by location_type (online, onsite, hybrid). Example: online
     * @queryParam from date Filter starts_at >= from. Example: 2026-01-01
     * @queryParam to date Filter starts_at <= to. Example: 2026-01-31
     * @queryParam sort string Sort (newest, oldest, starts_at). Example: newest
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();

        $filters = request()->only(['q', 'course_id', 'lesson_id', 'status', 'location_type', 'from', 'to', 'sort']);
        $perPage = (int) request()->get('per_page', 15);

        $sessions = $this->teacherSessionService->mySessions($teacher->id, $filters, $perPage);

        return $this->successResponse([
            'sessions' => SessionResource::collection($sessions),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        ], 'Sessions retrieved successfully.');
    }

    /**
     * Show Session (Owned by Teacher)
     */
    public function show(int $session): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();

        $model = $this->teacherSessionService->findTeacherSession($teacher->id, $session);

        if (!$model) {
            return $this->errorResponse(ApiErrorCode::NOT_FOUND, 'Session not found.', null, 404);
        }

        return $this->successResponse(new SessionResource($model), 'Session retrieved successfully.');
    }

    /**
     * Store Session (Owned by Teacher)
     *
     * ✅ Security:
     * - course_id MUST belong to teacher (owner_teacher_id)
     * - lesson_id (if provided) MUST belong to the same course
     * - teacher_id is forced from auth
     */
    public function store(StoreSessionRequest $request): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();
        $data = $request->validated();

        // forbid injection
        unset($data['teacher_id']);

        try {
            $session = $this->teacherSessionService->createSession($teacher->id, $data);
        } catch (\DomainException $e) {
            return match ($e->getMessage()) {
                'COURSE_NOT_OWNED' => $this->errorResponse(ApiErrorCode::FORBIDDEN, 'Course not owned by teacher.', null, 403),
                'LESSON_NOT_IN_COURSE' => $this->errorResponse(ApiErrorCode::VALIDATION_ERROR, 'Lesson must belong to the selected course.', null, 422),
                default => throw $e,
            };
        }

        return $this->successResponse(new SessionResource($session->load(['course', 'lesson'])), 'Session created successfully.', 201);
    }

    /**
     * Update Session (Owned by Teacher)
     */
    public function update(UpdateSessionRequest $request, int $session): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();

        $model = $this->teacherSessionService->findTeacherSession($teacher->id, $session);
        if (!$model) {
            return $this->errorResponse(ApiErrorCode::NOT_FOUND, 'Session not found.', null, 404);
        }

        $data = $request->validated();

        // forbid ownership changes
        unset($data['teacher_id'], $data['course_id']);

        try {
            $updated = $this->teacherSessionService->updateSession($teacher->id, $model, $data);
        } catch (\DomainException $e) {
            return match ($e->getMessage()) {
                'LESSON_NOT_IN_COURSE' => $this->errorResponse(ApiErrorCode::VALIDATION_ERROR, 'Lesson must belong to the selected course.', null, 422),
                default => throw $e,
            };
        }

        return $this->successResponse(new SessionResource($updated), 'Session updated successfully.');
    }

    /**
     * Delete Session (Owned by Teacher)
     */
    public function destroy(int $session): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();

        $model = $this->teacherSessionService->findTeacherSession($teacher->id, $session);
        if (!$model) {
            return $this->errorResponse(ApiErrorCode::NOT_FOUND, 'Session not found.', null, 404);
        }

        $this->teacherSessionService->deleteSession($model);

        return $this->successResponse(null, 'Session deleted successfully.');
    }
}
