<?php

namespace App\Http\Requests\Api\V1\Public;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|min:3|max:150',
            'educational_stage' => 'required|string|min:2|max:100',
            'phone' => 'required|string|min:7|max:20|regex:/^[\+]?[0-9\s\-]+$/',
            'gender' => 'required|string|in:male,female',
            'message' => 'required|string|min:10|max:2000',
            'course_id' => 'nullable|integer|exists:courses,id',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $phone = $this->input('phone');
            $normalized = preg_replace('/[\s\-]/', '', $phone);
            if (str_starts_with($normalized, '+20')) {
                $normalized = '+20' . preg_replace('/^\+20/', '', $normalized);
            }
            $this->merge(['phone' => $normalized]);
        }
    }
}
