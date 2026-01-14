<?php

namespace App\Http\Services;

use App\Domain\Training\Models\Program;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class ProgramService
{
    /* ============================================================
     |  PUBLIC (No Teacher Scope)
     |  Use only in /public endpoints
     * ============================================================ */

    /**
     * Compatibility method:
     * Some controllers call getPaginated(). We keep it mapped to publicIndex().
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->publicIndex($filters, $perPage);
    }

    /**
     * Public list of programs (default active only).
     */
    public function publicIndex(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Program::query();

        $this->applyCommonFilters($query, $filters, allowTeacherFilter: true); // public ممكن تسمح teacher_id لو محتاج
        $this->applySorting($query, (string) ($filters['sort'] ?? 'newest'));

        return $query->paginate($perPage);
    }

    /**
     * Public show (optionally with active courses only).
     */
    public function publicShow(int $programId, bool $withCourses = false): ?Program
    {
        $query = Program::query();

        if ($withCourses) {
            $query->with([
                'courses' => fn (Builder $q) => $q->where('is_active', true)
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc'),
                'courses.ownerTeacher',
            ]);
        }

        return $query->find($programId);
    }

    /**
     * Public program courses with filters.
     */
    public function publicProgramCourses(int $programId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $program = Program::query()->find($programId);

        if (! $program) {
            return $this->emptyPaginator($perPage);
        }

        $query = $program->courses();

        $this->applyCoursesFilters($query, $filters);
        $this->applyCoursesSorting($query, (string) ($filters['sort'] ?? 'newest'));

        return $query->paginate($perPage);
    }

    /* ============================================================
     |  TEACHER SCOPE (Secure)
     |  Use only in /teacher endpoints
     * ============================================================ */

    /**
     * Teacher paginated programs (secure).
     */
    public function teacherPaginated(int $teacherId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->getTeacherPrograms($teacherId, $filters, $perPage);
    }

    public function getTeacherPrograms(int $teacherId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Program::query()->where('teacher_id', $teacherId);

        $this->applyCommonFilters($query, $filters, allowTeacherFilter: false);

        // IMPORTANT: parent_id filter is allowed BUT should stay within teacher scope (already via where teacher_id)
        if (isset($filters['parent_id']) && $filters['parent_id'] !== null && $filters['parent_id'] !== '') {
            $query->where('parent_id', (int) $filters['parent_id']);
        }

        $this->applySorting($query, (string) ($filters['sort'] ?? 'newest'));

        return $query->paginate($perPage);
    }

    public function findTeacherProgram(int $teacherId, int $programId): ?Program
    {
        return Program::query()
            ->where('teacher_id', $teacherId)
            ->where('id', $programId)
            ->first();
    }

    public function findTeacherProgramOrFail(int $teacherId, int $programId): Program
    {
        $program = $this->findTeacherProgram($teacherId, $programId);

        if (! $program) {
            throw new ModelNotFoundException("Program not found.");
        }

        return $program;
    }

    public function createForTeacher(int $teacherId, array $data): Program
    {
        // never allow overriding teacher_id from request
        $data['teacher_id'] = $teacherId;

        // validate parent belongs to same teacher
        if (!empty($data['parent_id'])) {
            $this->assertParentBelongsToTeacher($teacherId, (int) $data['parent_id']);
        }

        return Program::create($data);
    }

    public function updateForTeacher(int $teacherId, int $programId, array $data): Program
    {
        unset($data['teacher_id']); // forbid ownership change

        $program = $this->findTeacherProgramOrFail($teacherId, $programId);

        if (array_key_exists('parent_id', $data) && !empty($data['parent_id'])) {
            $this->assertParentBelongsToTeacher($teacherId, (int) $data['parent_id']);
        }

        $program->update($data);

        return $program->refresh();
    }

    public function toggleActiveForTeacher(int $teacherId, int $programId): Program
    {
        $program = $this->findTeacherProgramOrFail($teacherId, $programId);

        $program->update(['is_active' => ! $program->is_active]);

        return $program->refresh();
    }

    /* ============================================================
     |  INTERNAL HELPERS (Programs)
     * ============================================================ */

    private function applyCommonFilters(Builder $query, array $filters, bool $allowTeacherFilter): void
    {
        // q search (name ar/en + code)
        if (!empty($filters['q'])) {
            $term = trim((string) $filters['q']);

            // حماية بسيطة من LIKE wildcards الثقيلة
            $term = str_replace(['%', '_'], ['\%', '\_'], $term);

            $query->where(function (Builder $q) use ($term) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name,'$.ar')) LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name,'$.en')) LIKE ?", ["%{$term}%"])
                  ->orWhere('code', 'LIKE', "%{$term}%");
            });
        }

        // active default=1 (only active)
        $active = $filters['active'] ?? 1;
        if ($active !== null && $active !== '') {
            $query->where('is_active', (bool) $active);
        }

        // code exact
        if (!empty($filters['code'])) {
            $query->where('code', (string) $filters['code']);
        }

        // optional: filter by teacher_id ONLY for public/admin usage
        if ($allowTeacherFilter && isset($filters['teacher_id']) && $filters['teacher_id'] !== null && $filters['teacher_id'] !== '') {
            $query->where('teacher_id', (int) $filters['teacher_id']);
        }

        // parent_id (public allowed only if you want)
        if (isset($filters['parent_id']) && $filters['parent_id'] !== null && $filters['parent_id'] !== '') {
            $query->where('parent_id', (int) $filters['parent_id']);
        }
    }

    private function applySorting(Builder $query, string $sort): void
    {
        $sort = strtolower(trim($sort));

        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'name', 'title' => $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name,'$.en')) ASC"),
            default  => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
        };
    }

    private function assertParentBelongsToTeacher(int $teacherId, int $parentId): void
    {
        $exists = Program::query()
            ->where('id', $parentId)
            ->where('teacher_id', $teacherId)
            ->exists();

        if (! $exists) {
            abort(422, 'parent_id must belong to the authenticated teacher.');
        }
    }

    /* ============================================================
     |  INTERNAL HELPERS (Courses)
     * ============================================================ */

    private function applyCoursesFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            $term = str_replace(['%', '_'], ['\%', '\_'], $term);

            $query->where(function (Builder $q) use ($term) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name,'$.ar')) LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name,'$.en')) LIKE ?", ["%{$term}%"])
                  ->orWhere('code', 'LIKE', "%{$term}%");
            });
        }

        $active = $filters['active'] ?? 1;
        if ($active !== null && $active !== '') {
            $query->where('is_active', (bool) $active);
        }

        if (!empty($filters['delivery_type'])) {
            $query->where('delivery_type', (string) $filters['delivery_type']);
        }

        if (!empty($filters['owner_teacher_id'])) {
            $query->where('owner_teacher_id', (int) $filters['owner_teacher_id']);
        }

        if (!empty($filters['has_price']) && (int) $filters['has_price'] === 1) {
            $query->whereHas('prices', fn (Builder $q) => $q->where('is_active', true));
        }
    }

    private function applyCoursesSorting(Builder $query, string $sort): void
    {
        $sort = strtolower(trim($sort));

        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'title', 'name' => $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name,'$.en')) ASC"),
            default => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
        };
    }

    private function emptyPaginator(int $perPage = 15): LengthAwarePaginator
    {
        return new Paginator([], 0, $perPage, 1, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }
}
