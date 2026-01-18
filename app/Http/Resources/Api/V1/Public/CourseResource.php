<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    use HasTranslatableFields;

    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'program_id' => $this->program_id,
            'owner_teacher_id' => $this->owner_teacher_id,

            'code' => $this->code,
            'name' => $this->getTranslatedValue($this->name, $locale),
            'description' => $this->getTranslatedValue($this->description, $locale),

            'image' => $this->image ? asset('storage/' . $this->image) : null,

            'delivery_type' => $this->delivery_type?->value ?? (string) $this->delivery_type,
            'duration_hours' => $this->duration_hours,
            'is_active' => (bool) $this->is_active,

            'prices' => CoursePriceResource::collection($this->whenLoaded('prices')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
