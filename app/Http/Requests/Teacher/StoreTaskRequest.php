<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    /**body
     * @bodyParam course_id integer required Example: 1
     * @bodyParam lesson_id integer optional Example: 1
     * @bodyParam title object required
     * @bodyParam title.ar string required Example: واجب 1
     * @bodyParam title.en string required Example: Task 1
     * @bodyParam description object optional
     * @bodyParam description.ar string optional Example: وصف
     * @bodyParam description.en string optional Example: Description
     * @bodyParam submission_type string required Example: file
     * @bodyParam max_score numeric optional Example: 10
     * @bodyParam due_date date optional Example: 2026-01-20
     * @bodyParam is_active boolean optional Example: 1
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'lesson_id' => ['nullable', 'integer', Rule::exists('lessons', 'id')],

            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],

            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],

            'submission_type' => ['required', 'string', Rule::in(['text','file','media','link','mixed'])],
            'max_score' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    /**body
     * @bodyParam title object required
     * @bodyParam title.ar string required Example: واجب 1
     * @bodyParam title.en string required Example: Task 1
     * @bodyParam description object optional
     * @bodyParam description.ar string optional Example: وصف
     * @bodyParam description.en string optional Example: Description
     * @bodyParam submission_type string required Example: file
     * @bodyParam max_score numeric optional Example: 10
     * @bodyParam due_date date optional Example: 2026-01-20
     * @bodyParam is_active boolean optional Example: 1
     */
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

        // flat translations support
        if ($this->hasAny(['title_ar', 'title_en'])) {
            $input['title'] = is_array($input['title'] ?? null) ? $input['title'] : [];
            if ($this->filled('title_ar')) $input['title']['ar'] = $this->input('title_ar');
            if ($this->filled('title_en')) $input['title']['en'] = $this->input('title_en');
        }

        if ($this->hasAny(['description_ar', 'description_en'])) {
            $input['description'] = is_array($input['description'] ?? null) ? $input['description'] : [];
            if ($this->has('description_ar')) $input['description']['ar'] = $this->input('description_ar');
            if ($this->has('description_en')) $input['description']['en'] = $this->input('description_en');
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
