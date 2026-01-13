<?php

namespace App\Http\Resources\Public\Api\V1;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseSectionResource extends JsonResource
{
    use HasTranslatableFields;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->getTranslatedValue($this->title, $locale), // Auto-translate
            'description' => $this->getTranslatedValue($this->description, $locale), // Auto-translate
            'sort_order' => $this->order, // Transform order to sort_order
            'description' => $this->description,
            'lesson_type' => $this->lesson_type?->value,
            'sort_order' => $this->sort_order,
            'is_preview' => (bool) $this->is_preview,
            'is_active' => (bool) $this->is_active,
            'estimated_minutes' => $this->estimated_minutes,
            'published_at' => optional($this->published_at)->toISOString(),

            'items_count' => $this->whenCounted('items'),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            'lessons' => LessonLiteResource::collection($this->whenLoaded('lessons')),
        ];
    }
}

