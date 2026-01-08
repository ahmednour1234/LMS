<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseSectionResource extends JsonResource
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
            'title' => $this->getTranslatedValue($this->title, $locale), // Auto-translate
            'description' => $this->getTranslatedValue($this->description, $locale), // Auto-translate
            'sort_order' => $this->order, // Transform order to sort_order
            'lessons' => LessonLiteResource::collection($this->whenLoaded('lessons')),
        ];
    }
}

