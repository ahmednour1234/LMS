<?php

namespace App\Http\Resources\Api\V1\Teacher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'lesson_id' => $this->lesson_id,

            'title' => $this->title,
            'description' => $this->description,

            'submission_type' => $this->submission_type,
            'max_score' => $this->max_score,
            'due_date' => $this->due_date?->toISOString(),
            'is_active' => (bool) $this->is_active,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'course' => $this->whenLoaded('course', fn() => [
                'id' => $this->course->id,
                'name' => $this->course->name,
            ]),
            'lesson' => $this->whenLoaded('lesson', fn() => [
                'id' => $this->lesson->id,
                'title' => $this->lesson->title,
            ]),
        ];
    }
}
