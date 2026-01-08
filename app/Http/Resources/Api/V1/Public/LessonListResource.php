<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonListResource extends JsonResource
{
    use HasTranslatableFields;

    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->getTranslatedValue($this->title, $locale),
            'description' => $this->getTranslatedValue($this->description, $locale),
            'type' => $this->lesson_type?->value,
            'is_preview' => $this->is_preview,
            'estimated_minutes' => $this->estimated_minutes,
            'published_at' => $this->published_at?->toIso8601String(),
            'sort_order' => $this->sort_order,
            'active' => $this->is_active,
            'section_id' => $this->section_id,
            'section' => $this->whenLoaded('section', function () use ($locale) {
                return [
                    'id' => $this->section->id,
                    'title' => $this->getTranslatedValue($this->section->title, $locale),
                    'sort_order' => $this->section->order,
                ];
            }),
            'course' => $this->whenLoaded('section.course', function () use ($locale) {
                return [
                    'id' => $this->section->course->id,
                    'title' => $this->getTranslatedValue($this->section->course->name, $locale),
                    'code' => $this->section->course->code,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
