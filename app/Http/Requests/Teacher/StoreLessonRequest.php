<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @bodyParam section_id integer required Example: 5
 * @bodyParam title object required
 * @bodyParam title.ar string required Example: درس 1
 * @bodyParam title.en string required Example: Lesson 1
 * @bodyParam description object optional
 * @bodyParam lesson_type string required recorded|live|mixed Example: recorded
 * @bodyParam sort_order integer optional Example: 1
 * @bodyParam is_preview boolean optional Example: 0
 * @bodyParam is_active boolean optional Example: 1
 * @bodyParam estimated_minutes integer optional Example: 20
 * @bodyParam published_at datetime optional Example: 2026-01-13 10:00:00
 */
class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', 'exists:course_sections,id'],

            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],

            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],

            'lesson_type' => ['required', 'string', Rule::in(['recorded', 'live', 'mixed'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_preview' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'estimated_minutes' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();
        foreach (['title', 'description'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $decoded = json_decode($input[$field], true);
                if (json_last_error() === JSON_ERROR_NONE) $input[$field] = $decoded;
            }
        }
        $this->replace($input);
    }
}
