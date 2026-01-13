<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'lesson_id' => $this->lesson_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type, // mcq/essay/mixed
            'total_score' => (float) $this->total_score,
            'duration_minutes' => $this->duration_minutes,
            'is_active' => (bool) $this->is_active,

            'questions_count' => $this->whenCounted('questions'),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
