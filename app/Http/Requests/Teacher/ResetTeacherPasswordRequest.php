<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class ResetTeacherPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:teachers,email',
            'code' => 'required|string|size:6',
            'password' => 'required|confirmed|min:8',
        ];
    }
}

