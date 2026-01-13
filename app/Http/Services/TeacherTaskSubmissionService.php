<?php

namespace App\Http\Services;

use App\Domain\Training\Models\TaskSubmission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TeacherTaskSubmissionService
{
    public function paginateForTask(int $teacherId, int $taskId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = TaskSubmission::query()
            ->where('task_id', $taskId)
            ->whereHas('task.course', fn(Builder $b) => $b->where('owner_teacher_id', $teacherId))
            ->with(['student','mediaFile','reviewer']);

        if (!empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        if (!empty($filters['q'])) {
            $term = $filters['q'];
            $q->whereHas('student', function (Builder $b) use ($term) {
                $b->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('email', 'LIKE', "%{$term}%");
            });
        }

        return $q->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findOwnedSubmission(int $teacherId, int $submissionId): ?TaskSubmission
    {
        return TaskSubmission::query()
            ->where('id', $submissionId)
            ->whereHas('task.course', fn(Builder $b) => $b->where('owner_teacher_id', $teacherId))
            ->with(['task','student','mediaFile','reviewer'])
            ->first();
    }

    public function review(int $teacherId, int $submissionId, int $reviewerUserId, array $data): TaskSubmission
    {
        $submission = $this->findOwnedSubmission($teacherId, $submissionId);
        abort_if(!$submission, 404, 'Submission not found.');

        // ✅ max_score guard
        if (array_key_exists('score', $data) && $data['score'] !== null) {
            $max = (float) ($submission->task?->max_score ?? 0);
            if ($max > 0 && (float)$data['score'] > $max) {
                abort(422, 'score cannot exceed task max_score.');
            }
        }

        $submission->update([
            'score' => $data['score'] ?? $submission->score,
            'feedback' => $data['feedback'] ?? $submission->feedback,
            'status' => $data['status'],
            'reviewed_by' => $reviewerUserId,
            'reviewed_at' => now(),
        ]);

        return $submission->refresh()->load(['task','student','mediaFile','reviewer']);
    }
}
