<?php

namespace App\Http\Requests\Api\V1\Student;

use Illuminate\Foundation\Http\FormRequest;

class SubmitTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text_answer' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
        ];
    }
}
