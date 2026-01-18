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

        $isEnrolled = false;
        $enrollmentStatus = null;

        if (auth('students')->check()) {
            $student = auth('students')->user();
            $enrollment = \App\Domain\Enrollment\Models\Enrollment::where('student_id', $student->id)
                ->where('course_id', $this->id)
                ->whereIn('status', ['active', 'pending', 'pending_payment'])
                ->first();

            $isEnrolled = $enrollment !== null;
            $enrollmentStatus = $enrollment?->status->value;
        }

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

            'is_enrolled' => $isEnrolled,
            'enrollment_status' => $enrollmentStatus,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
