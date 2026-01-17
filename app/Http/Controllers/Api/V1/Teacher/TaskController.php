<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\StoreTaskRequest;
use App\Http\Requests\Teacher\UpdateTaskRequest;
use App\Http\Resources\Api\V1\Teacher\TaskResource;
use App\Http\Services\TeacherTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Teacher Tasks
 *
 * Teacher-only APIs to manage Tasks.
 * Security:
 * - Teacher can only manage tasks under courses they own (owner_teacher_id = teacher).
 * - lesson_id (if provided) must belong to the same course_id.
 *
 * @authenticated
 * @header Authorization Bearer {TEACHER_JWT_TOKEN}
 */
class TaskController extends ApiController
{
    public function __construct(protected TeacherTaskService $service) {}
    /**
     * List My Tasks (Paginated)
     *
     * Return paginated tasks for courses owned by authenticated teacher.
     *
     * @queryParam q string Search in title(ar/en). Example: Homework
     * @queryParam course_id integer Filter by course. Example: 10
     * @queryParam lesson_id integer Filter by lesson. Example: 55
     * @queryParam active boolean Filter by active status. Example: 1
     * @queryParam submission_type string Filter by submission type (text,file,media,link,mixed). Example: file
     * @queryParam sort string Sort by newest|oldest. Example: newest
     * @queryParam per_page integer Items per page. Example: 15
     *
     * @response 200 {
     *  "success": true,
     *  "message": "Tasks retrieved successfully.",
     *  "data": {
     *    "tasks": [
     *      {
     *        "id": 1,
     *        "course_id": 10,
     *        "lesson_id": 55,
     *        "title": {"ar":"واجب 1","en":"Task 1"},
     *        "description": {"ar":"...","en":"..."},
     *        "submission_type": "file",
     *        "max_score": "10.00",
     *        "due_date": "2026-01-20T10:00:00.000000Z",
     *        "is_active": true,
     *        "created_at": "2026-01-13T10:00:00.000000Z",
     *        "updated_at": "2026-01-13T10:00:00.000000Z"
     *      }
     *    ],
     *    "meta": {"current_page":1,"last_page":1,"per_page":15,"total":1}
     *  }
     * }
     */
    public function index(): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();

        $filters = request()->only(['q','course_id','lesson_id','active','submission_type','sort']);
        $perPage = (int) request()->get('per_page', 15);

        $tasks = $this->service->paginate($teacher->id, $filters, $perPage);

        return $this->successResponse([
            'tasks' => TaskResource::collection($tasks),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ], 'Tasks retrieved successfully.');
    }

    /**
     * Show Task
     *
     * Show one task (only if owned by teacher via course ownership).
     *
     * @urlParam task integer required The task ID. Example: 1
     *
     * @response 200 {
     *  "success": true,
     *  "message": "Task retrieved successfully.",
     *  "data": {
     *    "id": 1,
     *    "course_id": 10,
     *    "lesson_id": 55,
     *    "title": {"ar":"واجب 1","en":"Task 1"},
     *    "submission_type": "file",
     *    "max_score": "10.00",
     *    "is_active": true
     *  }
     * }
     * @response 404 {"success":false,"message":"Task not found.","data":null}
     */
    public function show(int $task): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();

        $model = $this->service->findOwnedTask($teacher->id, $task);
        if (!$model) {
            return $this->errorResponse(\App\Http\Enums\ApiErrorCode::NOT_FOUND, 'Task not found.', null, 404);
        }

        return $this->successResponse(new TaskResource($model), 'Task retrieved successfully.');
    }

    /**
     * Create Task
     *
     * Create a new task under a course owned by teacher.
     * Security rules enforced:
     * - course_id must belong to teacher
     * - lesson_id (if provided) must belong to same course_id
     *
     * @bodyParam course_id integer required Example: 10
     * @bodyParam lesson_id integer optional Example: 55
     * @bodyParam title object required
     * @bodyParam title.ar string required Example: واجب 1
     * @bodyParam title.en string required Example: Task 1
     * @bodyParam description object optional
     * @bodyParam description.ar string optional Example: وصف
     * @bodyParam description.en string optional Example: Description
     * @bodyParam submission_type string required text|file|media|link|mixed Example: file
     * @bodyParam max_score numeric optional Example: 10
     * @bodyParam due_date date optional Example: 2026-01-20
     * @bodyParam is_active boolean optional Example: 1
     *
     * @response 201 {
     *  "success": true,
     *  "message": "Task created successfully.",
     *  "data": {
     *    "id": 1,
     *    "course_id": 10,
     *    "lesson_id": 55,
     *    "title": {"ar":"واجب 1","en":"Task 1"},
     *    "submission_type": "file"
     *  }
     * }
     * @response 422 {"success":false,"message":"course_id must belong to the authenticated teacher.","data":null}
     * @response 422 {"success":false,"message":"lesson_id must belong to the provided course_id.","data":null}
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();

        $task = $this->service->create($teacher->id, $request->validated());

        return $this->successResponse(new TaskResource($task), 'Task created successfully.', 201);
    }

    /**
     * Update Task
     *
     * Update a task owned by teacher.
     * If course_id or lesson_id changed:
     * - course_id must still belong to teacher
     * - lesson_id must belong to the same course_id
     *
     * @urlParam task integer required Example: 1
     * @bodyParam course_id integer optional Example: 10
     * @bodyParam lesson_id integer optional Example: 55
     * @bodyParam title object optional
     * @bodyParam title.ar string required_with:title Example: واجب 2
     * @bodyParam title.en string required_with:title Example: Task 2
     * @bodyParam description object optional
     * @bodyParam submission_type string optional text|file|media|link|mixed Example: text
     * @bodyParam max_score numeric optional Example: 15
     * @bodyParam due_date date optional Example: 2026-01-25
     * @bodyParam is_active boolean optional Example: 1
     *
     * @response 200 {"success":true,"message":"Task updated successfully.","data":{}}
     * @response 404 {"success":false,"message":"Task not found.","data":null}
     * @response 422 {"success":false,"message":"lesson_id must belong to the provided course_id.","data":null}
     */
    public function update(UpdateTaskRequest $request, int $task): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();

        $updated = $this->service->update($teacher->id, $task, $request->validated());

        return $this->successResponse(new TaskResource($updated), 'Task updated successfully.');
    }

    /**
     * Toggle Task Active
     *
     * @urlParam task integer required Example: 1
     *
     * @response 200 {"success":true,"message":"Task status updated successfully.","data":{}}
     * @response 404 {"success":false,"message":"Task not found.","data":null}
     */
    public function toggleActive(int $task): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();

        $updated = $this->service->toggleActive($teacher->id, $task);

        return $this->successResponse(new TaskResource($updated), 'Task status updated successfully.');
    }

    /**
     * Delete Task
     *
     * @urlParam task integer required Example: 1
     *
     * @response 200 {"success":true,"message":"Task deleted successfully.","data":null}
     * @response 404 {"success":false,"message":"Task not found.","data":null}
     */
    public function destroy(int $task): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();

        $this->service->delete($teacher->id, $task);

        return $this->successResponse(null, 'Task deleted successfully.');
    }
}
