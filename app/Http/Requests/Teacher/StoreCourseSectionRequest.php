<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam course_id integer required Example: 10
 * @bodyParam title object required
 * @bodyParam title.ar string required Example: القسم الأول
 * @bodyParam title.en string required Example: Section 1
 * @bodyParam description object optional
 * @bodyParam description.ar string optional
 * @bodyParam description.en string optional
 * @bodyParam order integer optional Example: 1
 * @bodyParam is_active boolean optional Example: 1
 */
class StoreCourseSectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
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
