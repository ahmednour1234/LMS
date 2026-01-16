<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class TeacherStudentsReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'course_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string'], // EnrollmentStatus
            'payment_status' => ['nullable', 'string'], // PaymentStatus
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
