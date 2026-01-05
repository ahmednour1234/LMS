<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title, // JSON object with ar/en
            'description' => $this->description, // JSON object with ar/en or null
            'type' => $this->lesson_type?->value, // Transform lesson_type to type
            'is_preview' => $this->is_preview,
            'estimated_minutes' => $this->estimated_minutes,
            'published_at' => $this->published_at?->toIso8601String(),
            'sort_order' => $this->sort_order,
            'active' => $this->is_active, // Transform is_active to active
            'section_id' => $this->section_id,
            'section' => [
                'id' => $this->whenLoaded('section')?->id,
                'title' => $this->whenLoaded('section')?->title,
                'sort_order' => $this->whenLoaded('section')?->order,
            ],
            'course' => [
                'id' => $this->whenLoaded('section.course')?->id,
                'title' => $this->whenLoaded('section.course')?->name,
                'code' => $this->whenLoaded('section.course')?->code,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

