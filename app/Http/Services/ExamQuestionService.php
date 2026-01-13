<?php

namespace App\Http\Services;

use App\Domain\Training\Models\ExamQuestion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExamQuestionService
{
    public function paginate(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $q = ExamQuestion::query();

        if (!empty($filters['exam_id'])) $q->where('exam_id', (int) $filters['exam_id']);
        if (!empty($filters['type'])) $q->where('type', $filters['type']);

        if (isset($filters['active']) && $filters['active'] !== null) {
            $q->where('is_active', (bool) $filters['active']);
        }

        $sort = $filters['sort'] ?? 'order';
        match ($sort) {
            'newest' => $q->orderByDesc('id'),
            'oldest' => $q->orderBy('id'),
            default => $q->orderBy('order')->orderBy('id'),
        };

        return $q->paginate($perPage);
    }

    public function create(array $data): ExamQuestion
    {
        $data['order'] = $data['order'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;

        return ExamQuestion::create($data);
    }

    public function bulkCreate(int $examId, array $questions): array
    {
        $created = [];
        foreach ($questions as $q) {
            $q['exam_id'] = $q['exam_id'] ?? $examId;
            $created[] = $this->create($q);
        }
        return $created;
    }

    public function update(ExamQuestion $question, array $data): ExamQuestion
    {
        $question->update($data);
        return $question->refresh();
    }

    public function delete(ExamQuestion $question): void
    {
        $question->delete();
    }
}
