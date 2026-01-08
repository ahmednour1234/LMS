<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseListResource extends JsonResource
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
            'title' => $this->getTranslatedValue($this->name, $locale), // Auto-translate based on locale
            'description' => $this->getTranslatedValue($this->description, $locale), // Auto-translate
            'active' => $this->is_active, // Transform is_active to active
            'program_id' => $this->program_id,
            'program' => $this->whenLoaded('program', function () use ($locale) {
                return [
                    'id' => $this->program->id,
                    'name' => $this->getTranslatedValue($this->program->name, $locale),
                    'code' => $this->program->code,
                ];
            }),
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name, // Branch name is not JSON, return as is
                ];
            }),
            'delivery_type' => $this->delivery_type?->value,
            'duration_hours' => $this->duration_hours,
            'code' => $this->code,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

