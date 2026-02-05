<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseBookingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'educational_stage' => $this->educational_stage,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'message' => $this->message,
            'course_id' => $this->course_id,
            'course' => $this->whenLoaded('course', fn() => [
                'id' => $this->course->id,
                'code' => $this->course->code,
                'name' => $this->course->name,
            ]),
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
