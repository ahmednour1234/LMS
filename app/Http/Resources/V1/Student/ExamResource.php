<?php

namespace App\Http\Resources\V1\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'total_score' => (float) $this->total_score,
            'duration_minutes' => $this->duration_minutes,
            'has_attempt' => $this->has_attempt ?? false,
            'last_attempt_at' => $this->last_attempt_at ?? null,
            'best_score' => $this->best_score ?? null,
        ];
    }
}
