<?php

namespace App\Http\Services;

use App\Domain\Training\Models\Program;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProgramService
{
    /**
     * Get paginated programs with filtering and sorting.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Program::query();

        // Filter by search query (name in ar/en)
        if (isset($filters['q']) && !empty($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$searchTerm}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$searchTerm}%"]);
            });
        }

        // Filter by active status (default: active only)
        $active = $filters['active'] ?? 1;
        if ($active !== null) {
            $query->where('is_active', (bool) $active);
        }

        // Filter by branch_id (optional)
        if (isset($filters['branch_id']) && $filters['branch_id'] !== null) {
            $query->where('branch_id', $filters['branch_id']);
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'name' => $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) ASC"),
            default => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'), // newest
        };

        return $query->paginate($perPage);
    }

    /**
     * Get a single program by ID.
     *
     * @param int $id
     * @return Program|null
     */
    public function findById(int $id): ?Program
    {
        return Program::find($id);
    }

    /**
     * Get courses for a program with filtering.
     *
     * @param int $programId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getProgramCourses(int $programId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $program = $this->findById($programId);
        
        if (!$program) {
            return \Illuminate\Pagination\LengthAwarePaginator::make([], 0, $perPage);
        }

        $query = $program->courses();

        // Apply course filters (same as CourseService)
        // Filter by search query (title in ar/en)
        if (isset($filters['q']) && !empty($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$searchTerm}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$searchTerm}%"]);
            });
        }

        // Filter by active status (default: active only)
        $active = $filters['active'] ?? 1;
        if ($active !== null) {
            $query->where('is_active', (bool) $active);
        }

        // Filter by branch_id (optional)
        if (isset($filters['branch_id']) && $filters['branch_id'] !== null) {
            $query->where('branch_id', $filters['branch_id']);
        }

        // Filter by delivery_type (optional)
        if (isset($filters['delivery_type']) && !empty($filters['delivery_type'])) {
            $query->where('delivery_type', $filters['delivery_type']);
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
}

