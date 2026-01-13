<?php

namespace App\Http\Services;

use App\Domain\Training\Models\LessonItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LessonItemService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = LessonItem::query()->with('mediaFile')->with('lesson');

        if (!empty($filters['lesson_id'])) {
            $q->where('lesson_id', (int) $filters['lesson_id']);
        }

        if (!empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }

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

    public function create(array $data): LessonItem
    {
        $data['order'] = $data['order'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;

        return LessonItem::create($data);
    }

    public function update(LessonItem $item, array $data): LessonItem
    {
        $item->update($data);
        return $item->refresh();
    }

    public function toggleActive(LessonItem $item, ?bool $active = null): LessonItem
    {
        $item->is_active = $active ?? !$item->is_active;
        $item->save();
        return $item->refresh();
    }
}
