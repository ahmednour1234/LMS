<?php

namespace App\Http\Requests\Api\V1\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|integer|exists:courses,id',
            'delivery_type' => 'required|string|in:online,onsite,hybrid',
            'branch_id' => 'required_if:delivery_type,onsite|nullable|integer|exists:branches,id',
            'pricing_mode' => 'nullable|string|in:course_total,per_session',
            'selected_price_option_id' => 'nullable|integer|exists:course_prices,id',
            'sessions_purchased' => 'nullable|integer|min:1|required_if:pricing_mode,per_session',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required_if' => 'Branch is required for onsite courses.',
            'sessions_purchased.required_if' => 'Sessions purchased is required for per-session pricing.',
        ];
    }
}
