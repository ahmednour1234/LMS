<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in(['video', 'pdf', 'file', 'link'])],

            'title' => ['sometimes', 'required', 'array'],
            'title.ar' => ['sometimes', 'required', 'string', 'max:255'],
            'title.en' => ['sometimes', 'required', 'string', 'max:255'],

            'media_file_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'external_url' => ['nullable', 'url', 'max:2000'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $type = $this->input('type', $this->route('item')?->type);

            if (in_array($type, ['link', 'video'])) {
                if ($this->has('external_url') && !$this->filled('external_url')) {
                    $v->errors()->add('external_url', 'external_url cannot be empty for link/video type.');
                }
            } else {
                if ($this->has('media_file_id') && !$this->filled('media_file_id')) {
                    $v->errors()->add('media_file_id', 'media_file_id cannot be empty for pdf/file type.');
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
