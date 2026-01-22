<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
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
