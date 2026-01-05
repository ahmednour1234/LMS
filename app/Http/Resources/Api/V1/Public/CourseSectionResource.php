<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseSectionResource extends JsonResource
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
            'title' => $this->title, // JSON object with ar/en
            'description' => $this->description, // JSON object with ar/en or null
            'sort_order' => $this->order, // Transform order to sort_order
            'lessons' => LessonLiteResource::collection($this->whenLoaded('lessons')),
        ];
    }
}

