<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamQuestionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in(['mcq', 'essay'])],
            'question' => ['sometimes', 'required', 'array'],
            'question.ar' => ['sometimes', 'required', 'string'],
            'question.en' => ['sometimes', 'required', 'string'],
            'options' => ['nullable', 'array'],
            'correct_answer' => ['nullable'],
            'points' => ['nullable', 'numeric', 'min:0'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $type = $this->input('type', $this->route('question')?->type);
            if ($type === 'mcq') {
                if ($this->has('options') && (!is_array($this->input('options')) || empty($this->input('options')))) {
                    $v->errors()->add('options', 'options required for mcq.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();
        foreach (['question','options'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $decoded = json_decode($input[$field], true);
                if (json_last_error() === JSON_ERROR_NONE) $input[$field] = $decoded;
            }
        }
        $this->replace($input);
    }
}
