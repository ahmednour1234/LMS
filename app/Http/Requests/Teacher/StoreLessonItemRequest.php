<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @bodyParam lesson_id integer required Example: 12
 * @bodyParam type string required video|pdf|file|link Example: video
 * @bodyParam title object required
 * @bodyParam title.ar string required
 * @bodyParam title.en string required
 * @bodyParam media_file_id integer optional Example: 33
 * @bodyParam external_url string optional Example: https://youtube.com/...
 * @bodyParam order integer optional Example: 1
 * @bodyParam is_active boolean optional Example: 1
 */
class StoreLessonItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'type' => ['required', 'string', Rule::in(['video', 'pdf', 'file', 'link'])],

            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],

            'media_file_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'external_url' => ['nullable', 'url', 'max:2000'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],

            // must provide either media_file_id OR external_url depending on type
            'type' => [
                'required',
                'string',
                Rule::in(['video', 'pdf', 'file', 'link']),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $type = $this->input('type');

            if ($type === 'link' || $type === 'video') {
                if (!$this->filled('external_url')) {
                    $v->errors()->add('external_url', 'external_url is required for link/video type.');
                }
            } else {
                if (!$this->filled('media_file_id')) {
                    $v->errors()->add('media_file_id', 'media_file_id is required for pdf/file type.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();
        if (isset($input['title']) && is_string($input['title'])) {
            $decoded = json_decode($input['title'], true);
            if (json_last_error() === JSON_ERROR_NONE) $input['title'] = $decoded;
        }
        $this->replace($input);
    }
}
