<?php

namespace App\Http\Resources\Public;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonLiteResource extends JsonResource
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
            'type' => $this->lesson_type?->value, // Transform lesson_type to type
            'is_preview' => $this->is_preview,
            'estimated_minutes' => $this->estimated_minutes,
            'published_at' => $this->published_at?->toIso8601String(),
            'sort_order' => $this->sort_order,
        ];
    }
}

