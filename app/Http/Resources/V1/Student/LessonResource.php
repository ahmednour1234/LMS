<?php

namespace App\Http\Resources\V1\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'lesson_type' => $this->lesson_type?->value,
            'estimated_minutes' => $this->estimated_minutes,
            'is_preview' => $this->is_preview,
            'items' => LessonItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
