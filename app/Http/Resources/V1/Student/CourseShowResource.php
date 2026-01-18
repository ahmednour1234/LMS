<?php

namespace App\Http\Resources\V1\Student;

use App\Http\Resources\Api\V1\Public\CourseSectionResource;
use App\Support\Helpers\ImageHelper;
use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseShowResource extends JsonResource
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
            'sections_summary' => $this->whenLoaded('sections', function () {
                return $this->sections->map(fn($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'lessons_count' => $s->lessons->count() ?? 0,
                ]);
            }),
            'pricing_options' => $this->whenLoaded('prices', function () {
                return $this->prices->map(fn($p) => [
                    'id' => $p->id,
                    'price' => (float) $p->price,
                    'session_price' => (float) ($p->session_price ?? 0),
                    'pricing_mode' => $p->pricing_mode,
                ]);
            }),
            'enrolled_status' => $this->enrolled_status ?? 'not_enrolled',
            'can_access_content' => $this->can_access_content ?? false,
            'remaining_amount' => (float) ($this->remaining_amount ?? 0),
        ];
    }
}
