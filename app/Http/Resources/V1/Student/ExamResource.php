<?php

namespace App\Http\Resources\V1\Student;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    use HasTranslatableFields;

    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->getTranslatedValue($this->title, $locale),
            'description' => $this->getTranslatedValue($this->description, $locale),
            'type' => $this->type,
            'total_score' => (float) $this->total_score,
            'duration_minutes' => $this->duration_minutes,
            'has_attempt' => $this->has_attempt ?? false,
            'last_attempt_at' => $this->last_attempt_at ?? null,
            'best_score' => $this->best_score ?? null,
        ];
    }
}
