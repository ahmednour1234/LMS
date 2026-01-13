<?php

namespace App\Http\Services;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CoursePrice;
use App\Domain\Training\Models\Program;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TeacherCourseService
{
    public function myCourses(int $teacherId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Course::query()
            ->where('owner_teacher_id', $teacherId)
            ->with(['program']);

        // ✅ if filtering by program_id => ensure it's teacher program
        if (!empty($filters['program_id'])) {
            $programId = (int) $filters['program_id'];

            $query->whereHas('program', function (Builder $q) use ($teacherId, $programId) {
                $q->where('id', $programId)->where('teacher_id', $teacherId);
            });
        }

        if (!empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(function (Builder $q) use ($term) {
                $q->where('code', 'LIKE', "%{$term}%")
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name,'$.ar')) LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name,'$.en')) LIKE ?", ["%{$term}%"]);
            });
        }

        $active = $filters['active'] ?? 1;
        if ($active !== null && $active !== '') {
            $query->where('is_active', (bool) $active);
        }

        if (!empty($filters['delivery_type'])) {
            $query->where('delivery_type', $filters['delivery_type']);
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'name' => $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name,'$.en')) ASC"),
            default => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
        };

        return $query->paginate($perPage);
    }

    public function findTeacherCourse(int $teacherId, int $courseId): ?Course
    {
        return Course::query()
            ->where('owner_teacher_id', $teacherId)
            ->where('id', $courseId)
            ->with(['program', 'prices'])
            ->first();
    }

    public function createCourse(int $teacherId, array $data): Course
    {
        // ✅ validate program belongs to teacher
        $this->assertProgramBelongsToTeacher($teacherId, (int) $data['program_id']);

        return DB::transaction(function () use ($teacherId, $data) {
            $prices = $data['prices'] ?? [];
            unset($data['prices']);

            // enforce ownership
            $data['owner_teacher_id'] = $teacherId;
            $data['is_active'] = $data['is_active'] ?? true;

            $course = Course::create($data);

            // ✅ create multiple prices (online + onsite etc.)
            foreach ($prices as $p) {
                CoursePrice::create([
                    'course_id' => $course->id,
                    'branch_id' => null,
                    'delivery_type' => $p['delivery_type'],
                    'pricing_mode' => $p['pricing_mode'] ?? 'course_total',
                    'price' => $p['price'] ?? null,
                    'session_price' => $p['session_price'] ?? null,
                    'sessions_count' => $p['sessions_count'] ?? null,
                    'allow_installments' => $p['allow_installments'] ?? false,
                    'min_down_payment' => $p['min_down_payment'] ?? null,
                    'max_installments' => $p['max_installments'] ?? null,
                    'is_active' => $p['is_active'] ?? true,
                ]);
            }

            return $course->load(['program', 'prices']);
        });
    }

    public function updateCourse(int $teacherId, int $courseId, array $data): Course
    {
        $course = $this->findTeacherCourse($teacherId, $courseId);
        abort_if(!$course, 404, 'Course not found.');

        // ✅ if program_id is changing => must also belong to teacher
        if (array_key_exists('program_id', $data) && $data['program_id'] !== null) {
            $this->assertProgramBelongsToTeacher($teacherId, (int) $data['program_id']);
        }

        return DB::transaction(function () use ($course, $data) {
            $prices = $data['prices'] ?? null;
            unset($data['prices']);

            // forbid owner change
            unset($data['owner_teacher_id']);

            $course->update($data);

            // ✅ prices update strategy:
            // updateOrCreate by (course_id + delivery_type + branch_id null)
            if (is_array($prices)) {
                foreach ($prices as $p) {
                    CoursePrice::updateOrCreate(
                        [
                            'course_id' => $course->id,
                            'branch_id' => null,
                            'delivery_type' => $p['delivery_type'],
                        ],
                        [
                            'pricing_mode' => $p['pricing_mode'] ?? 'course_total',
                            'price' => $p['price'] ?? null,
                            'session_price' => $p['session_price'] ?? null,
                            'sessions_count' => $p['sessions_count'] ?? null,
                            'allow_installments' => $p['allow_installments'] ?? false,
                            'min_down_payment' => $p['min_down_payment'] ?? null,
                            'max_installments' => $p['max_installments'] ?? null,
                            'is_active' => $p['is_active'] ?? true,
                        ]
                    );
                }
            }

            return $course->refresh()->load(['program', 'prices']);
        });
    }

    public function toggleActive(Course $course): Course
    {
        $course->update(['is_active' => ! $course->is_active]);
        return $course->refresh();
    }

    private function assertProgramBelongsToTeacher(int $teacherId, int $programId): void
    {
        $ok = Program::query()
            ->where('id', $programId)
            ->where('teacher_id', $teacherId)
            ->exists();

        abort_unless($ok, 422, 'program_id must belong to the authenticated teacher.');
    }
}
