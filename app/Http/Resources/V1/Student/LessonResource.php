<?php

namespace App\Http\Resources\V1\Student;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    use HasTranslatableFields;

    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->getTranslatedValue($this->title, $locale),
            'description' => $this->getTranslatedValue($this->description, $locale),
            'lesson_type' => $this->lesson_type?->value,
            'estimated_minutes' => $this->estimated_minutes,
            'is_preview' => $this->is_preview,
            'items' => LessonItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
