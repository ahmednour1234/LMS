<?php

namespace App\Http\Services;

use App\Domain\Branch\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BranchService
{
    /**
     * Get paginated branches with filtering.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Branch::query();

        // Filter by search query (name)
        if (isset($filters['q']) && !empty($filters['q'])) {
            $query->where('name', 'like', '%' . $filters['q'] . '%');
        }

        // Filter by active status (default: active only)
        $active = $filters['active'] ?? 1;
        if ($active !== null) {
            $query->where('is_active', (bool) $active);
        }

        return $query->orderBy('name')->paginate($perPage);
    }
}

