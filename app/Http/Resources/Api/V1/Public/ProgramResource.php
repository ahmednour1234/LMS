<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Support\Helpers\ImageHelper;
use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
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
            'name' => $this->getTranslatedValue($this->name, $locale), // Auto-translate
            'description' => $this->getTranslatedValue($this->description, $locale), // Auto-translate
            'active' => $this->is_active,
            'code' => $this->code,
            'image' => ImageHelper::getFullImageUrl($this->image),
            'parent_id' => $this->parent_id,
            'courses' => CourseListResource::collection($this->whenLoaded('courses')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

