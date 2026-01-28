<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Support\Helpers\ImageHelper;
use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseShowResource extends JsonResource
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
            'title' => $this->getTranslatedValue($this->name, $locale), // Auto-translate
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
                    'name' => $this->branch->name, // Branch name is not JSON
                ];
            }),
            'owner_teacher_id' => $this->owner_teacher_id,
            'owner_teacher' => $this->whenLoaded('ownerTeacher', function () {
                return [
                    'id' => $this->ownerTeacher->id,
                    'name' => $this->ownerTeacher->name,
                    'email' => $this->ownerTeacher->email,
                ];
            }),
            'delivery_type' => $this->delivery_type?->value,
            'duration_hours' => $this->duration_hours,
            'code' => $this->code,
            'image' => ImageHelper::getFullImageUrl($this->image),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'prices' => CoursePriceResource::collection($this->whenLoaded('prices')),
            'sections' => CourseSectionResource::collection($this->whenLoaded('sections')),
            'is_enrolled' => $isEnrolled,
            'enrollment_status' => $enrollmentStatus,
        ];
    }
}

