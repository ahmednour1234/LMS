<?php

namespace App\Http\Services;

use App\Domain\Training\Models\Lesson;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class LessonService
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Lesson::with(['section.course']);

        if (isset($filters['q']) && !empty($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar')) LIKE ?", ["%{$searchTerm}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$searchTerm}%"]);
            });
        }

        if (isset($filters['section_id']) && $filters['section_id'] !== null) {
            $query->where('section_id', $filters['section_id']);
        }

        if (isset($filters['course_id']) && $filters['course_id'] !== null) {
            $query->whereHas('section', function (Builder $q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        if (isset($filters['lesson_type']) && !empty($filters['lesson_type'])) {
            $query->where('lesson_type', $filters['lesson_type']);
        }

        $active = $filters['active'] ?? 1;
        if ($active !== null) {
            $query->where('is_active', (bool) $active);
        }

        if (isset($filters['is_preview']) && $filters['is_preview'] !== null) {
            $query->where('is_preview', (bool) $filters['is_preview']);
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'title' => $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) ASC"),
            'sort_order' => $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc'),
            default => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
        };

        return $query->paginate($perPage);
    }
}
