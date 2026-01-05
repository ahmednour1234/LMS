<?php

namespace App\Http\Requests\Api\V1\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'sex' => 'nullable|in:male,female',
            'image' => 'nullable|file|image|max:5120', // 5MB max
        ];
    }
}

