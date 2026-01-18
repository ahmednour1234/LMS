<?php

namespace App\Http\Resources\V1\Student;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    use HasTranslatableFields;

    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->getTranslatedValue($this->title, $locale),
            'description' => $this->getTranslatedValue($this->description, $locale),
            'submission_type' => $this->submission_type,
            'max_score' => (float) $this->max_score,
            'due_date' => $this->due_date?->toISOString(),
            'submission_status' => $this->submission_status ?? 'not_submitted',
            'submission_score' => $this->submission_score ?? null,
            'submitted_at' => $this->submitted_at ?? null,
        ];
    }
}
