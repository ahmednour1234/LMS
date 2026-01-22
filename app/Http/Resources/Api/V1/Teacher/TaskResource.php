<?php

namespace App\Http\Resources\Api\V1\Teacher;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'course_id' => $this->course_id,
            'lesson_id' => $this->lesson_id,

            'title' => $this->getTranslatedValue($this->title, $locale),
            'description' => $this->getTranslatedValue($this->description, $locale),

            'submission_type' => $this->submission_type,
            'max_score' => $this->max_score,
            'due_date' => $this->due_date?->toISOString(),
            'is_active' => (bool) $this->is_active,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'course' => $this->whenLoaded('course', fn() => [
                'id' => $this->course->id,
                'name' => $this->getTranslatedValue($this->course->name, $locale),
            ]),
            'lesson' => $this->whenLoaded('lesson', fn() => [
                'id' => $this->lesson->id,
                'title' => $this->getTranslatedValue($this->lesson->title, $locale),
            ]),
        ];
    }
}
