<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRevenueReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],

            'course_id' => ['nullable', 'integer', 'min:1'],
            'payment_status' => ['nullable', 'string'], // هنفلترها في السيرفس حسب enum/values

            'group_by' => ['nullable', Rule::in(['day', 'week', 'month'])],
        ];
    }
}
