<?php

namespace App\Http\Requests\Api\V1\Student;

use Illuminate\Foundation\Http\FormRequest;

class SubmitExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:exam_questions,id',
            'answers.*.answer' => 'nullable',
            'answers.*.answer_text' => 'nullable|string',
            'answers.*.selected_option' => 'nullable|integer',
        ];
    }
}
