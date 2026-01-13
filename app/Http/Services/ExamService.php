<?php

namespace App\Http\Services;

use App\Domain\Training\Models\Exam;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ExamService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = Exam::query()->withCount('questions');

        if (!empty($filters['course_id'])) $q->where('course_id', (int) $filters['course_id']);
        if (!empty($filters['lesson_id'])) $q->where('lesson_id', (int) $filters['lesson_id']);
        if (!empty($filters['type'])) $q->where('type', $filters['type']);

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

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $q->orderBy('id'),
            default => $q->orderByDesc('id'),
        };

        return $q->paginate($perPage);
    }

    public function create(array $data): Exam
    {
        $data['is_active'] = $data['is_active'] ?? true;
        return Exam::create($data);
    }

    public function update(Exam $exam, array $data): Exam
    {
        $exam->update($data);
        return $exam->refresh();
    }

    public function toggleActive(Exam $exam, ?bool $active = null): Exam
    {
        $exam->is_active = $active ?? !$exam->is_active;
        $exam->save();
        return $exam->refresh();
    }
}
