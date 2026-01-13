<?php

namespace App\Http\Services;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TeacherTaskService
{
    public function paginate(int $teacherId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = Task::query()
            ->whereHas('course', fn(Builder $b) => $b->where('owner_teacher_id', $teacherId))
            ->with(['course', 'lesson']);

        if (!empty($filters['course_id'])) {
            $courseId = (int) $filters['course_id'];
            $q->where('course_id', $courseId);
        }

        if (!empty($filters['lesson_id'])) {
            $q->where('lesson_id', (int) $filters['lesson_id']);
        }

        if (!empty($filters['active']) || $filters['active'] === '0') {
            $q->where('is_active', (bool) $filters['active']);
        }

        if (!empty($filters['submission_type'])) {
            $q->where('submission_type', $filters['submission_type']);
        }

        if (!empty($filters['q'])) {
            $term = $filters['q'];
            $q->where(function (Builder $b) use ($term) {
                $b->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title,'$.ar')) LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title,'$.en')) LIKE ?", ["%{$term}%"]);
            });
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $q->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            default => $q->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
        };

        return $q->paginate($perPage);
    }

    public function findOwnedTask(int $teacherId, int $taskId): ?Task
    {
        return Task::query()
            ->where('id', $taskId)
            ->whereHas('course', fn(Builder $b) => $b->where('owner_teacher_id', $teacherId))
            ->with(['course', 'lesson'])
            ->first();
    }

    public function create(int $teacherId, array $data): Task
    {
        $courseId = (int) $data['course_id'];
        $lessonId = $data['lesson_id'] ?? null;

        $this->assertCourseOwned($teacherId, $courseId);
        $this->assertLessonBelongsToCourse($courseId, $lessonId);

        return DB::transaction(function () use ($data) {
            $data['is_active'] = $data['is_active'] ?? true;
            $task = Task::create($data);
            return $task->load(['course','lesson']);
        });
    }

    public function update(int $teacherId, int $taskId, array $data): Task
    {
        $task = $this->findOwnedTask($teacherId, $taskId);
        abort_if(!$task, 404, 'Task not found.');

        $courseId = array_key_exists('course_id', $data) ? (int) $data['course_id'] : $task->course_id;
        $lessonId = array_key_exists('lesson_id', $data) ? $data['lesson_id'] : $task->lesson_id;

        $this->assertCourseOwned($teacherId, $courseId);
        $this->assertLessonBelongsToCourse($courseId, $lessonId);

        $task->update($data);
        return $task->refresh()->load(['course','lesson']);
    }

    public function toggleActive(int $teacherId, int $taskId): Task
    {
        $task = $this->findOwnedTask($teacherId, $taskId);
        abort_if(!$task, 404, 'Task not found.');

        $task->update(['is_active' => ! $task->is_active]);
        return $task->refresh();
    }

    public function delete(int $teacherId, int $taskId): void
    {
        $task = $this->findOwnedTask($teacherId, $taskId);
        abort_if(!$task, 404, 'Task not found.');

        $task->delete();
    }

    private function assertCourseOwned(int $teacherId, int $courseId): void
    {
        $ok = Course::query()
            ->where('id', $courseId)
            ->where('owner_teacher_id', $teacherId)
            ->exists();

        abort_unless($ok, 422, 'course_id must belong to the authenticated teacher.');
    }

    private function assertLessonBelongsToCourse(int $courseId, $lessonId): void
    {
        if ($lessonId === null || $lessonId === '') return;

        $ok = Lesson::query()
            ->where('id', (int) $lessonId)
            ->whereHas('section', fn(Builder $b) => $b->where('course_id', $courseId))
            ->exists();

        abort_unless($ok, 422, 'lesson_id must belong to the provided course_id.');
    }
}
