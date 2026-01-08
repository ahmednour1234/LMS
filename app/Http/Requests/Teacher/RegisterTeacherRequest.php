<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class RegisterTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:teachers,email|max:255',
            'password' => 'required|string|min:8',
            'sex' => 'required|in:male,female',
            'photo' => 'nullable|image',
        ];
    }
}

