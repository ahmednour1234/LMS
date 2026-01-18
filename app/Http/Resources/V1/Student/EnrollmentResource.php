<?php

namespace App\Http\Resources\V1\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'course' => $this->whenLoaded('course', function () {
                return [
                    'id' => $this->course->id,
                    'name' => $this->course->name,
                    'code' => $this->course->code,
                ];
            }),
            'program' => $this->whenLoaded('course.program', function () {
                return [
                    'id' => $this->course->program->id,
                    'name' => $this->course->program->name,
                ];
            }),
            'status' => $this->status->value,
            'enrollment_mode' => $this->enrollment_mode->value,
            'delivery_type' => $this->delivery_type,
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                ];
            }),
            'total_amount' => (float) ($this->total_amount ?? 0),
            'paid_amount' => (float) ($this->paid_amount ?? 0),
            'due_amount' => (float) ($this->due_amount ?? 0),
            'payment_status' => $this->payment_status ?? 'unpaid',
            'currency_code' => $this->currency_code ?? 'OMR',
            'registered_at' => $this->registered_at?->toISOString(),
            'enrolled_at' => $this->enrolled_at?->toISOString(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
        ];
    }
}
