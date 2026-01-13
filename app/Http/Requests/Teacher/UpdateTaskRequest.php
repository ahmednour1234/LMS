<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    /**body
     * @bodyParam course_id integer optional Example: 1
     * @bodyParam lesson_id integer optional Example: 1
     * @bodyParam title object optional
     * @bodyParam title.ar string required_with:title Example: واجب 2
     * @bodyParam title.en string required_with:title Example: Task 2
     * @bodyParam description object optional
     * @bodyParam submission_type string optional text|file|media|link|mixed Example: text
     * @bodyParam max_score numeric optional Example: 15
     * @bodyParam due_date date optional Example: 2026-01-25
     * @bodyParam is_active boolean optional Example: 1
     */
    public function rules(): array
    {
        return [
            'course_id' => ['sometimes', 'integer', Rule::exists('courses', 'id')],
            'lesson_id' => ['nullable', 'integer', Rule::exists('lessons', 'id')],

            'title' => ['sometimes', 'array'],
            'title.ar' => ['required_with:title', 'string', 'max:255'],
            'title.en' => ['required_with:title', 'string', 'max:255'],

            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],

            'submission_type' => ['sometimes', 'string', Rule::in(['text','file','media','link','mixed'])],
            'max_score' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        foreach (['title','description'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $decoded = json_decode($input[$field], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $input[$field] = $decoded;
                }
            }
        }

        foreach (['is_active'] as $b) {
            if ($this->has($b)) {
                $input[$b] = filter_var($this->input($b), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    ?? (bool) $this->input($b);
            }
        }

        $this->replace($input);
    }
}
