<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @bodyParam course_id integer required Example: 10
 * @bodyParam lesson_id integer nullable Example: 77
 * @bodyParam title object required
 * @bodyParam title.ar string required
 * @bodyParam title.en string required
 * @bodyParam description object nullable
 * @bodyParam type string required mcq|essay|mixed Example: mcq
 * @bodyParam total_score numeric nullable Example: 10
 * @bodyParam duration_minutes integer nullable Example: 30
 * @bodyParam is_active boolean nullable Example: 1
 */
class StoreExamRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],

            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],

            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],

            'type' => ['required', 'string', Rule::in(['mcq', 'essay', 'mixed'])],
            'total_score' => ['nullable', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();
        foreach (['title','description'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $decoded = json_decode($input[$field], true);
                if (json_last_error() === JSON_ERROR_NONE) $input[$field] = $decoded;
            }
        }
        $this->replace($input);
    }
}
