<?php

namespace App\Http\Resources\Public\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'sex' => $this->sex,
            'photo' => $this->photo,
            'active' => $this->active,
            'owned_courses_count' => $this->when(isset($this->owned_courses_count), $this->owned_courses_count),
            'assigned_courses_count' => $this->when(isset($this->assigned_courses_count), $this->assigned_courses_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

