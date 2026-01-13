<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * supports single OR bulk:
 * - single: exam_id + fields
 * - bulk: questions[] each item contains fields
 */
class StoreExamQuestionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'exam_id' => ['required_without:questions', 'integer', 'exists:exams,id'],

            // bulk mode
            'questions' => ['required_without:exam_id', 'array', 'min:1'],
            'questions.*.exam_id' => ['nullable', 'integer', 'exists:exams,id'],

            'type' => ['required_without:questions', 'string', Rule::in(['mcq', 'essay'])],
            'question' => ['required_without:questions', 'array'],
            'question.ar' => ['required_without:questions', 'string'],
            'question.en' => ['required_without:questions', 'string'],
            'options' => ['nullable', 'array'],
            'correct_answer' => ['nullable'],
            'points' => ['required_without:questions', 'numeric', 'min:0'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],

            // bulk fields
            'questions.*.type' => ['required_with:questions', 'string', Rule::in(['mcq', 'essay'])],
            'questions.*.question' => ['required_with:questions', 'array'],
            'questions.*.question.ar' => ['required_with:questions', 'string'],
            'questions.*.question.en' => ['required_with:questions', 'string'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.correct_answer' => ['nullable'],
            'questions.*.points' => ['required_with:questions', 'numeric', 'min:0'],
            'questions.*.order' => ['nullable', 'integer', 'min:0'],
            'questions.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            // Validate MCQ requirements
            if ($this->filled('questions')) {
                foreach ((array) $this->input('questions') as $i => $q) {
                    if (($q['type'] ?? null) === 'mcq') {
                        if (empty($q['options']) || !is_array($q['options'])) {
                            $v->errors()->add("questions.$i.options", 'options required for mcq.');
                        }
                        if (!array_key_exists('correct_answer', $q)) {
                            $v->errors()->add("questions.$i.correct_answer", 'correct_answer required for mcq.');
                        }
                    }
                }
                return;
            }

            if ($this->input('type') === 'mcq') {
                if (!$this->filled('options') || !is_array($this->input('options'))) {
                    $v->errors()->add('options', 'options required for mcq.');
                }
                if (!$this->has('correct_answer')) {
                    $v->errors()->add('correct_answer', 'correct_answer required for mcq.');
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

        if (isset($input['questions']) && is_string($input['questions'])) {
            $decoded = json_decode($input['questions'], true);
            if (json_last_error() === JSON_ERROR_NONE) $input['questions'] = $decoded;
        }

        $this->replace($input);
    }
}
