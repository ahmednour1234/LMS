<?php

namespace App\Http\Services;

use App\Domain\Training\Models\CourseSection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CourseSectionService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = CourseSection::query()->with('course');

        if (!empty($filters['course_id'])) {
            $q->where('course_id', (int) $filters['course_id']);
        }

        if (isset($filters['active']) && $filters['active'] !== null) {
            $q->where('is_active', (bool) $filters['active']);
        }

        if (!empty($filters['q'])) {
            $term = $filters['q'];
            $q->where(function (Builder $b) use ($term) {
                $b->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar')) LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$term}%"]);
            });
        }

        $sort = $filters['sort'] ?? 'order';
        match ($sort) {
            'newest' => $q->orderByDesc('id'),
            'oldest' => $q->orderBy('id'),
            default => $q->orderBy('order')->orderBy('id'),
        };

        return $q->paginate($perPage);
    }

    public function create(array $data): CourseSection
    {
        $data['order'] = $data['order'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;

        return CourseSection::create($data);
    }

    public function update(CourseSection $section, array $data): CourseSection
    {
        $section->update($data);
        return $section->refresh();
    }

    public function toggleActive(CourseSection $section, ?bool $active = null): CourseSection
    {
        $section->is_active = $active ?? !$section->is_active;
        $section->save();
        return $section->refresh();
    }
}
