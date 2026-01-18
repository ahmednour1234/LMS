<?php

namespace App\Http\Resources\V1\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamAnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'answer' => $this->answer,
            'points_earned' => (float) $this->points_earned,
            'points_possible' => (float) $this->points_possible,
            'is_correct' => $this->is_correct,
            'feedback' => $this->feedback,
        ];
    }
}
