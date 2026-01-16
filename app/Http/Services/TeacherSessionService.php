<?php

namespace App\Http\Services;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\CourseSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TeacherSessionService
{
    public function mySessions(int $teacherId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = CourseSession::query()
            ->where('teacher_id', $teacherId)
            ->with(['course', 'lesson']);

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function findTeacherSession(int $teacherId, int $sessionId): ?CourseSession
    {
        return CourseSession::query()
            ->where('teacher_id', $teacherId)
            ->with(['course', 'lesson'])
            ->find($sessionId);
    }

    /**
     * ✅ Security:
     * - course MUST belong to teacher (owner_teacher_id)
     * - lesson (if provided) MUST belong to same course
     * - teacher_id is forced from auth
     */
    public function createSession(int $teacherId, array $data): CourseSession
    {
        $course = Course::query()
            ->where('id', $data['course_id'])
            ->where('owner_teacher_id', $teacherId)
            ->first();

        if (!$course) {
            throw new \DomainException('COURSE_NOT_OWNED');
        }

        if (!empty($data['lesson_id'])) {
            $lessonOk = Lesson::query()
                ->where('id', $data['lesson_id'])
                ->where('course_id', $course->id)
                ->exists();

            if (!$lessonOk) {
                throw new \DomainException('LESSON_NOT_IN_COURSE');
            }
        }

        $data['teacher_id'] = $teacherId;

        return CourseSession::create($data);
    }

    /**
     * ✅ Security:
     * - teacher can update only his session
     * - course_id cannot be moved
     * - lesson must remain within same course
     */
    public function updateSession(int $teacherId, CourseSession $session, array $data): CourseSession
    {
        // forbid moving between courses
        unset($data['course_id'], $data['teacher_id']);

        if (array_key_exists('lesson_id', $data) && !empty($data['lesson_id'])) {
            $lessonOk = Lesson::query()
                ->where('id', $data['lesson_id'])
                ->where('course_id', $session->course_id)
                ->exists();

            if (!$lessonOk) {
                throw new \DomainException('LESSON_NOT_IN_COURSE');
            }
        }

        $session->update($data);

        return $session->fresh(['course', 'lesson']);
    }

    public function deleteSession(CourseSession $session): void
    {
        $session->delete();
    }

    private function applyFilters(Builder $q, array $filters): void
    {
        if (!empty($filters['course_id'])) {
            $q->where('course_id', (int) $filters['course_id']);
        }

        if (!empty($filters['lesson_id'])) {
            $q->where('lesson_id', (int) $filters['lesson_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $q->where('status', $filters['status']);
        }

        if (isset($filters['location_type']) && $filters['location_type'] !== '') {
            $q->where('location_type', $filters['location_type']);
        }

        if (!empty($filters['from'])) {
            $q->whereDate('starts_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $q->whereDate('starts_at', '<=', $filters['to']);
        }

        if (!empty($filters['q'])) {
            $term = trim($filters['q']);
            $q->where(function (Builder $qq) use ($term) {
                // title is json array => search both ar/en (MySQL JSON_SEARCH) fallback LIKE
                $qq->where('title->ar', 'like', "%{$term}%")
                   ->orWhere('title->en', 'like', "%{$term}%");
            });
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $q->orderBy('starts_at', 'asc'),
            'starts_at' => $q->orderBy('starts_at', 'desc'),
            default => $q->orderByDesc('id'),
        };
    }
}
