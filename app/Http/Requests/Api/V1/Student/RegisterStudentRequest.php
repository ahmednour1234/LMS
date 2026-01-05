<?php

namespace App\Http\Requests\Api\V1\Student;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStudentRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:students,email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'sex' => 'nullable|in:male,female',
            'branch_id' => 'nullable|exists:branches,id',
            'student_code' => 'nullable|string|max:50|unique:students,student_code',
            'national_id' => 'nullable|string|max:50',
        ];
    }
}

