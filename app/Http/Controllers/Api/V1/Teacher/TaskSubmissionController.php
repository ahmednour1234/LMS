<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\ReviewTaskSubmissionRequest;
use App\Http\Resources\Api\V1\Teacher\TaskSubmissionResource;
use App\Http\Services\TeacherTaskService;
use App\Http\Services\TeacherTaskSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Teacher Task Submissions
 */
class TaskSubmissionController extends ApiController
{
    public function __construct(
        protected TeacherTaskService $taskService,
        protected TeacherTaskSubmissionService $submissionService,
    ) {}

    /**
     * List Task Submissions
     *
     * @urlParam task integer required Example: 1
     * @queryParam q string optional Search submissions by student name or email.
     * @queryParam status string optional Filter submissions by status (pending, reviewed, approved, rejected).
     * @queryParam per_page integer optional Number of submissions per page. Default: 15.
     *
     * @response 200 {"success":true,"message":"Submissions retrieved successfully.","data":{}}
     * @response 404 {"success":false,"message":"Task not found.","data":null}
     */
    public function index(int $task): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();

        // ✅ ensures task belongs to teacher
        $ownedTask = $this->taskService->findOwnedTask($teacher->id, $task);
        if (!$ownedTask) {
            return $this->errorResponse(\App\Http\Enums\ApiErrorCode::NOT_FOUND, 'Task not found.', null, 404);
        }

        $filters = request()->only(['q','status']);
        $perPage = (int) request()->get('per_page', 15);

        $subs = $this->submissionService->paginateForTask($teacher->id, $task, $filters, $perPage);

        return $this->successResponse([
            'submissions' => TaskSubmissionResource::collection($subs),
            'meta' => [
                'current_page' => $subs->currentPage(),
                'last_page' => $subs->lastPage(),
                'per_page' => $subs->perPage(),
                'total' => $subs->total(),
            ],
        ], 'Submissions retrieved successfully.');
    }

    /**
     * Show Task Submission
     *
     * @urlParam submission integer required Example: 1
     *
     * @response 200 {"success":true,"message":"Submission retrieved successfully.","data":{}}
     * @response 404 {"success":false,"message":"Submission not found.","data":null}
     */
    public function show(int $submission): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();

        $model = $this->submissionService->findOwnedSubmission($teacher->id, $submission);
        if (!$model) {
            return $this->errorResponse(\App\Http\Enums\ApiErrorCode::NOT_FOUND, 'Submission not found.', null, 404);
        }

        return $this->successResponse(new TaskSubmissionResource($model), 'Submission retrieved successfully.');
    }
    /**
     * Review Task Submission
     *
     * @urlParam submission integer required Example: 1
     * @bodyParam review object required
     * @bodyParam review.score numeric required Example: 10
     * @bodyParam review.feedback string required Example: Good work!
     *
     * @response 200 {"success":true,"message":"Submission reviewed successfully.","data":{}}
     * @response 404 {"success":false,"message":"Submission not found.","data":null}
     */
    public function review(ReviewTaskSubmissionRequest $request, int $submission): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();
        $reviewerUserId = Auth::id(); // لو مستخدم admin مختلف، عدّل حسب نظامك

        $updated = $this->submissionService->review($teacher->id, $submission, $reviewerUserId, $request->validated());

        return $this->successResponse(new TaskSubmissionResource($updated), 'Submission reviewed successfully.');
    }
}
