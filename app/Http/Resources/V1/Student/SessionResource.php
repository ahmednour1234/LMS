<?php

namespace App\Http\Resources\V1\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'location_type' => $this->location_type?->value,
            'room_slug' => $this->room_slug,
            'status' => $this->status?->value,
            'lesson' => $this->whenLoaded('lesson', function () {
                return [
                    'id' => $this->lesson->id,
                    'title' => $this->lesson->title,
                ];
            }),
            'teacher' => $this->whenLoaded('teacher', function () {
                return [
                    'id' => $this->teacher->id,
                    'name' => $this->teacher->name,
                ];
            }),
            'attendance_status' => $this->attendance_status ?? 'absent',
            'attendance_marked_at' => $this->attendance_marked_at ?? null,
        ];
    }
}
