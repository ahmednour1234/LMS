<?php

namespace App\Http\Services;

use App\Domain\Training\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CourseService
{
    /**
     * Get paginated courses with filtering and sorting.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Course::with(['program.branch']);

        // Filter by search query (title in ar/en - stored as 'name' in DB)
        if (isset($filters['q']) && !empty($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$searchTerm}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$searchTerm}%"]);
            });
        }

        // Filter by program_id (optional)
        if (isset($filters['program_id']) && $filters['program_id'] !== null) {
            $query->where('program_id', $filters['program_id']);
        }

        // Filter by active status (default: active only)
        $active = $filters['active'] ?? 1;
        if ($active !== null) {
            $query->where('is_active', (bool) $active);
        }

        // Filter by branch_id (optional) - through program relationship
        if (isset($filters['branch_id']) && $filters['branch_id'] !== null) {
            $query->whereHas('program', function (Builder $q) use ($filters) {
                $q->where('programs.branch_id', $filters['branch_id']);
            });
        }

        // Filter by delivery_type (optional)
        if (isset($filters['delivery_type']) && !empty($filters['delivery_type'])) {
            $query->where('delivery_type', $filters['delivery_type']);
        }

        // Filter by owner_teacher_id (optional)
        if (isset($filters['owner_teacher_id']) && $filters['owner_teacher_id'] !== null) {
            $query->where('owner_teacher_id', $filters['owner_teacher_id']);
        }

        // Filter by teacher_id (optional) - courses where teacher is assigned or owner
        if (isset($filters['teacher_id']) && $filters['teacher_id'] !== null) {
            $teacherId = $filters['teacher_id'];
            $query->where(function (Builder $q) use ($teacherId) {
                $q->where('owner_teacher_id', $teacherId)
                  ->orWhereHas('teachers', function (Builder $teacherQuery) use ($teacherId) {
                      $teacherQuery->where('teachers.id', $teacherId);
                  });
            });
        }

        // Filter by has_price (only courses with active prices)
        if (isset($filters['has_price']) && $filters['has_price'] == 1) {
            $query->whereHas('prices', function (Builder $q) {
                $q->where('is_active', true);
            });
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'title' => $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) ASC"),
            default => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'), // newest
        };

        return $query->paginate($perPage);
    }

    /**
     * Get a single course by ID with sections and lessons.
     *
     * @param int $id
     * @return Course|null
     */
    public function findByIdWithContent(int $id): ?Course
    {
        return Course::with([
            'program',
            'branch',
            'ownerTeacher',
            'teachers',
            'sections' => function ($query) {
                $query->where('is_active', true)
                      ->orderBy('order')
                      ->orderBy('id');
            },
            'sections.lessons' => function ($query) {
                $query->where('is_active', true)
                      ->orderBy('sort_order')
                      ->orderBy('id');
            }
        ])->find($id);
    }

    /**
     * Get a single course by ID (without relations).
     *
     * @param int $id
     * @return Course|null
     */
    public function findById(int $id): ?Course
    {
        return Course::find($id);
    }

    /**
     * Get course prices with filtering and ordering.
     *
     * @param int $courseId
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCoursePrices(int $courseId, array $filters = [])
    {
        $course = $this->findById($courseId);
        
        if (!$course) {
            return collect([]);
        }

        $query = $course->prices();

        // Filter by active status (default: active only)
        $active = $filters['active'] ?? 1;
        if ($active !== null) {
            $query->where('is_active', (bool) $active);
        }

        // Filter by branch_id (optional)
        $branchId = $filters['branch_id'] ?? null;
        
        // Filter by delivery_type (optional)
        $deliveryType = $filters['delivery_type'] ?? null;

        // Apply filters - show matching branch_id OR null (global), same for delivery_type
        // We don't filter here, we just order to show exact matches first

        // Ordering: exact match first, then null (global)
        // 1. Exact branch_id match first (if filter provided), then null branch_id
        if ($branchId !== null) {
            $query->orderByRaw('CASE WHEN branch_id = ? THEN 0 WHEN branch_id IS NULL THEN 1 ELSE 2 END', [$branchId]);
        } else {
            $query->orderByRaw('CASE WHEN branch_id IS NULL THEN 0 ELSE 1 END');
        }

        // 2. Exact delivery_type match first (if filter provided), then null delivery_type
        if ($deliveryType !== null) {
            $query->orderByRaw('CASE WHEN delivery_type = ? THEN 0 WHEN delivery_type IS NULL THEN 1 ELSE 2 END', [$deliveryType]);
        } else {
            $query->orderByRaw('CASE WHEN delivery_type IS NULL THEN 0 ELSE 1 END');
        }

        // 3. Final ordering
        $query->orderBy('branch_id')
              ->orderBy('delivery_type')
              ->orderBy('id');

        return $query->get();
    }
}

