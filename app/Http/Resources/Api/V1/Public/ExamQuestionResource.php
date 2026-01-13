<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamQuestionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'type' => $this->type, // mcq/essay
            'question' => $this->question,
            'options' => $this->options, // for mcq
            'correct_answer' => $this->correct_answer,
            'points' => (float) $this->points,
            'order' => $this->order,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
