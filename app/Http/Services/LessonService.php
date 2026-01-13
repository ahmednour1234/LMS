<?php

namespace App\Http\Services;

use App\Domain\Training\Models\Lesson;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class LessonService
{

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = Lesson::query()->with('section');

        if (!empty($filters['section_id'])) {
            $q->where('section_id', (int) $filters['section_id']);
        }

        if (isset($filters['active']) && $filters['active'] !== null) {
            $q->where('is_active', (bool) $filters['active']);
        }

        if (isset($filters['is_preview']) && $filters['is_preview'] !== null) {
            $q->where('is_preview', (bool) $filters['is_preview']);
        }

        if (!empty($filters['lesson_type'])) {
            $q->where('lesson_type', $filters['lesson_type']);
        }

        if (!empty($filters['q'])) {
            $term = $filters['q'];
            $q->where(function (Builder $b) use ($term) {
                $b->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar')) LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$term}%"]);
            });
        }

        $sort = $filters['sort'] ?? 'sort_order';
        match ($sort) {
            'newest' => $q->orderByDesc('id'),
            'oldest' => $q->orderBy('id'),
            default => $q->orderBy('sort_order')->orderBy('id'),
        };

        return $q->paginate($perPage);
    }

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
    public function create(array $data): Lesson
    {
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_preview'] = $data['is_preview'] ?? false;
        $data['is_active'] = $data['is_active'] ?? true;

        return Lesson::create($data);
    }

    public function update(Lesson $lesson, array $data): Lesson
    {
        $lesson->update($data);
        return $lesson->refresh();
    }

    public function toggleActive(Lesson $lesson, ?bool $active = null): Lesson
    {
        $lesson->is_active = $active ?? !$lesson->is_active;
        $lesson->save();
        return $lesson->refresh();
    }
}
