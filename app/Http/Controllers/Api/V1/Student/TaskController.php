<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Http\Requests\Api\V1\Student\SubmitTaskRequest;
use App\Domain\Training\Models\Task;
use App\Domain\Training\Models\TaskSubmission;
use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Media\Models\MediaFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * @group Student Tasks
 *
 * Student API for viewing and submitting tasks.
 */
class TaskController extends ApiController
{
    /**
     * List Tasks
     *
     * Get all tasks for a course with submission status.
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Tasks retrieved successfully.",
     *   "data": [...]
     * }
     */
    public function index(Request $request, int $course): JsonResponse
    {
        $student = auth('students')->user();
        $courseModel = $request->attributes->get('course');

        $tasks = Task::where('course_id', $course)
            ->where('is_active', true)
            ->get();

        $taskIds = $tasks->pluck('id')->toArray();
        $submissions = TaskSubmission::where('student_id', $student->id)
            ->whereIn('task_id', $taskIds)
            ->get()
            ->keyBy('task_id');

        $tasks->transform(function ($task) use ($submissions) {
            $submission = $submissions->get($task->id);
            $task->setAttribute('submission_status', $submission ? 'submitted' : 'not_submitted');
            $task->setAttribute('submission_score', $submission ? (float) ($submission->score ?? 0) : null);
            $task->setAttribute('submitted_at', $submission?->created_at?->toISOString());
            return $task;
        });

        return $this->successResponse(
            \App\Http\Resources\V1\Student\TaskResource::collection($tasks),
            'Tasks retrieved successfully.'
        );
    }

    /**
     * Show Task
     *
     * Get task details (requires enrollment).
     *
     * @urlParam task integer required The ID of the task. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Task retrieved successfully.",
     *   "data": {
     *     "id": 1,
     *     "title": {...},
     *     "max_score": 100.00,
     *     "due_date": "2026-01-31T23:59:59Z"
     *   }
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "You are not enrolled in this course.",
     *   "error": {
     *     "code": "FORBIDDEN"
     *   }
     * }
     */
    public function show(int $task): JsonResponse
    {
        $student = auth('students')->user();
        $taskModel = Task::with('course')->find($task);

        if (!$taskModel) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Task not found.',
                null,
                404
            );
        }

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $taskModel->course_id)
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
            new \App\Http\Resources\V1\Student\TaskResource($taskModel),
            'Task retrieved successfully.'
        );
    }

    /**
     * Submit Task
     *
     * Submit a task with optional file upload.
     *
     * @urlParam task integer required The ID of the task. Example: 1
     * @bodyParam text_answer string optional Text answer. Example: "My answer here"
     * @bodyParam file file optional File upload (max 10MB). Example: (binary)
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Task submitted successfully.",
     *   "data": {
     *     "id": 1,
     *     "task_id": 1,
     *     "submission_text": "My answer",
     *     "status": "submitted"
     *   }
     * }
     * @response 400 {
     *   "success": false,
     *   "message": "Task submission deadline has passed.",
     *   "error": {
     *     "code": "BUSINESS_RULE_VIOLATION"
     *   }
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "The provided data is invalid.",
     *   "error": {
     *     "code": "VALIDATION_ERROR"
     *   }
     * }
     */
    public function submit(SubmitTaskRequest $request, int $task): JsonResponse
    {
        $student = auth('students')->user();
        $taskModel = Task::with('course')->findOrFail($task);

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $taskModel->course_id)
            ->whereIn('status', ['active', 'pending', 'pending_payment'])
            ->firstOrFail();

        if ($taskModel->due_date && now()->gt($taskModel->due_date)) {
            return $this->errorResponse(
                ApiErrorCode::BUSINESS_RULE_VIOLATION,
                'Task submission deadline has passed.',
                null,
                400
            );
        }

        $mediaFileId = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('task-submissions', 'public');
            
            $mediaFile = MediaFile::create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
            
            $mediaFileId = $mediaFile->id;
        }

        $submission = TaskSubmission::create([
            'task_id' => $taskModel->id,
            'student_id' => $student->id,
            'submission_text' => $request->input('text_answer'),
            'media_file_id' => $mediaFileId,
            'status' => 'submitted',
        ]);

        return $this->createdResponse(
            new \App\Http\Resources\V1\Student\TaskSubmissionResource($submission),
            'Task submitted successfully.'
        );
    }

    /**
     * List Task Submissions
     *
     * Get all submissions for a task.
     *
     * @urlParam task integer required The ID of the task. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Task submissions retrieved successfully.",
     *   "data": [...]
     * }
     */
    public function submissions(int $task): JsonResponse
    {
        $student = auth('students')->user();
        $taskModel = Task::findOrFail($task);

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $taskModel->course_id)
            ->whereIn('status', ['active', 'pending', 'pending_payment'])
            ->firstOrFail();

        $submissions = TaskSubmission::where('task_id', $task)
            ->where('student_id', $student->id)
            ->with('mediaFile')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            \App\Http\Resources\V1\Student\TaskSubmissionResource::collection($submissions),
            'Task submissions retrieved successfully.'
        );
    }
}
