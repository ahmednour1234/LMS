<?php

namespace App\Http\Resources\V1\Student;

use App\Support\Helpers\ImageHelper;
use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseCardResource extends JsonResource
{
    use HasTranslatableFields;

    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->getTranslatedValue($this->name, $locale),
            'description' => $this->getTranslatedValue($this->description, $locale),
            'program' => $this->whenLoaded('program', function () use ($locale) {
                return [
                    'id' => $this->program->id,
                    'name' => $this->getTranslatedValue($this->program->name, $locale),
                ];
            }),
            'delivery_type' => $this->delivery_type?->value,
            'image' => ImageHelper::getFullImageUrl($this->image),
            'sessions_count' => $this->whenLoaded('sessions', fn() => $this->sessions->count()),
            'is_enrolled' => $this->is_enrolled ?? false,
            'enrollment_status' => $this->enrollment_status ?? null,
            'payment_status' => $this->payment_status ?? null,
            'price_summary' => [
                'total' => $this->when(isset($this->total_amount), fn() => (float) ($this->total_amount ?? 0)),
                'paid' => $this->when(isset($this->paid_amount), fn() => (float) ($this->paid_amount ?? 0)),
                'due' => $this->when(isset($this->due_amount), fn() => (float) ($this->due_amount ?? 0)),
            ],
        ];
    }
}
