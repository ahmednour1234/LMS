<?php

namespace App\Http\Resources\V1\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'question' => $this->question,
            'options' => $this->options,
            'points' => (float) $this->points,
            'order' => $this->order,
        ];
    }
}
