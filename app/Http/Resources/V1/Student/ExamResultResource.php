<?php

namespace App\Http\Resources\V1\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'score' => (float) $this->score,
            'max_score' => (float) $this->max_score,
            'percentage' => (float) $this->percentage,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'graded_at' => $this->graded_at?->toISOString(),
            'answers' => ExamAnswerResource::collection($this->whenLoaded('answers')),
        ];
    }
}
