<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam title object optional
 * @bodyParam title.ar string optional Example: قسم
 * @bodyParam title.en string optional Example: Section
 * @bodyParam description object optional
 * @bodyParam description.ar string optional
 * @bodyParam description.en string optional
 * @bodyParam order integer optional Example: 2
 * @bodyParam is_active boolean optional Example: 1
 */
class UpdateCourseSectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'array'],
            'title.ar' => ['sometimes', 'required', 'string', 'max:255'],
            'title.en' => ['sometimes', 'required', 'string', 'max:255'],

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
