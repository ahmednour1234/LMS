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

    public function publicIndex(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Program::query();

        $this->applyCommonFilters($query, $filters, allowTeacherFilter: false);
        $this->applySorting($query, $filters['sort'] ?? 'newest');

        return $query->paginate($perPage);
    }

    public function publicShow(int $programId, bool $withCourses = false): ?Program
    {
        $query = Program::query();

        if ($withCourses) {
            $query->with([
                'courses' => fn ($q) => $q->where('is_active', true)
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc'),
                'courses.ownerTeacher',
            ]);
        }

        return $query->find($programId);
    }

    public function publicProgramCourses(int $programId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $program = Program::query()->find($programId);

        if (!$program) {
            return $this->emptyPaginator($perPage);
        }

        $query = $program->courses();

        // courses filters (light)
        if (!empty($filters['q'])) {
            $term = $filters['q'];
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
            $query->where('delivery_type', $filters['delivery_type']);
        }

        if (!empty($filters['owner_teacher_id'])) {
            $query->where('owner_teacher_id', (int) $filters['owner_teacher_id']);
        }

        if (!empty($filters['has_price']) && (int) $filters['has_price'] === 1) {
            $query->whereHas('prices', fn (Builder $q) => $q->where('is_active', true));
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'title', 'name' => $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name,'$.en')) ASC"),
            default => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
        };

        return $query->paginate($perPage);
    }

    /* ============================================================
     |  TEACHER SCOPE (Secure)
     |  Use only in /teacher endpoints
     * ============================================================ */

    public function getTeacherPrograms(int $teacherId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Program::query()->where('teacher_id', $teacherId);

        $this->applyCommonFilters($query, $filters, allowTeacherFilter: false);

        // IMPORTANT: parent_id filter must also belong to same teacher (prevent leakage)
        if (isset($filters['parent_id']) && $filters['parent_id'] !== null && $filters['parent_id'] !== '') {
            $query->where('parent_id', (int) $filters['parent_id']);
        }

        $this->applySorting($query, $filters['sort'] ?? 'newest');

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

        if (!$program) {
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
     |  INTERNAL HELPERS
     * ============================================================ */

    private function applyCommonFilters(Builder $query, array $filters, bool $allowTeacherFilter): void
    {
        // q search (name ar/en + code)
        if (!empty($filters['q'])) {
            $term = $filters['q'];
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
            $query->where('code', $filters['code']);
        }

        // optional: filter by teacher_id ONLY for admin/public use if needed
        if ($allowTeacherFilter && isset($filters['teacher_id']) && $filters['teacher_id'] !== null && $filters['teacher_id'] !== '') {
            $query->where('teacher_id', (int) $filters['teacher_id']);
        }
    }

    private function applySorting(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'name'   => $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name,'$.en')) ASC"),
            default  => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
        };
    }

    private function assertParentBelongsToTeacher(int $teacherId, int $parentId): void
    {
        $exists = Program::query()
            ->where('id', $parentId)
            ->where('teacher_id', $teacherId)
            ->exists();

        if (!$exists) {
            abort(422, 'parent_id must belong to the authenticated teacher.');
        }
    }

    private function emptyPaginator(int $perPage = 15): LengthAwarePaginator
    {
        return new Paginator([], 0, $perPage, 1, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }
}
