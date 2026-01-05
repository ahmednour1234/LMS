<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseShowResource extends JsonResource
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
            'title' => $this->name, // Transform name to title (JSON object with ar/en)
            'description' => $this->description, // JSON object with ar/en or null
            'active' => $this->is_active, // Transform is_active to active
            'program_id' => $this->program_id,
            'branch_id' => $this->branch_id,
            'delivery_type' => $this->delivery_type?->value,
            'duration_hours' => $this->duration_hours,
            'code' => $this->code,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'sections' => CourseSectionResource::collection($this->whenLoaded('sections')),
        ];
    }
}

