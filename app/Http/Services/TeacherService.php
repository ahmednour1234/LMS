<?php

namespace App\Http\Services;

use App\Domain\Training\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TeacherService
{
    /**
     * Get paginated teachers with filtering and sorting.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Teacher::query();

        // Filter by search query (name or email)
        if (isset($filters['q']) && !empty($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by active status (default: active only)
        $active = $filters['active'] ?? 1;
        if ($active !== null) {
            $query->where('active', (bool) $active);
        }

        // Filter by sex (optional)
        if (isset($filters['sex']) && !empty($filters['sex'])) {
            $query->where('sex', $filters['sex']);
        }

        // Filter by has_courses (teachers with assigned/owned courses)
        if (isset($filters['has_courses']) && $filters['has_courses'] == 1) {
            $query->where(function (Builder $q) {
                $q->whereHas('ownedCourses')
                  ->orWhereHas('assignedCourses');
            });
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'name' => $query->orderBy('name', 'asc'),
            'email' => $query->orderBy('email', 'asc'),
            default => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'), // newest
        };

        return $query->paginate($perPage);
    }

    /**
     * Get a single teacher by ID with relations.
     *
     * @param int $id
     * @return Teacher|null
     */
    public function findById(int $id): ?Teacher
    {
        return Teacher::with(['ownedCourses', 'assignedCourses'])->find($id);
    }
}

