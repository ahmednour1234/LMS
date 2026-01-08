<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'sex' => 'nullable|in:male,female',
            'photo' => 'nullable|image',
            'password' => 'nullable|string|min:8',
        ];
    }
}

