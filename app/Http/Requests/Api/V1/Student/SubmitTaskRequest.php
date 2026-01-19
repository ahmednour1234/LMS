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
            'solve' => 'nullable|string',
            'pdf' => 'nullable|file|mimes:pdf|max:10240',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasSolve = $this->filled('solve');
            $hasPdf = $this->hasFile('pdf');
            
            if (!$hasSolve && !$hasPdf) {
                $validator->errors()->add('solve', 'Either solve (text answer) or pdf file is required.');
            }
        });
    }
}
