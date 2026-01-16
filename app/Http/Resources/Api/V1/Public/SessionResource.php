<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course->id,
                'name' => $this->course->name ?? null,
                'code' => $this->course->code ?? null,
            ]),

            'lesson' => $this->whenLoaded('lesson', fn () => $this->lesson ? [
                'id' => $this->lesson->id,
                'title' => $this->lesson->title ?? null,
            ] : null),

            'title' => $this->title,

            'location_type' => $this->location_type?->value,
            'location_type_label' => $this->location_type?->label(),

            'provider' => $this->provider?->value,
            'provider_label' => $this->provider?->label(),

            'starts_at' => optional($this->starts_at)->toISOString(),
            'ends_at' => optional($this->ends_at)->toISOString(),

            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),

            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
