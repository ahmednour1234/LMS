<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @bodyParam parent_id integer optional Parent program id. Example: 3
 * @bodyParam code string required Program code (unique). Example: PRG-001
 * @bodyParam name object required Program name translations.
 * @bodyParam name.ar string required Arabic name. Example: برنامج التدريب
 * @bodyParam name.en string required English name. Example: Training Program
 * @bodyParam description object optional Program description translations.
 * @bodyParam description.ar string optional Arabic description. Example: وصف البرنامج
 * @bodyParam description.en string optional English description. Example: Program description
 * @bodyParam is_active boolean optional Active status (default true). Example: 1
 * @bodyParam image file optional Program image (jpg,png,webp) max 5MB.
 */
class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // protected via auth:teacher middleware
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists('programs', 'id')],
            'code'      => ['required', 'string', 'max:50', Rule::unique('programs', 'code')],

            // translations
            'name'      => ['required', 'array'],
            'name.ar'   => ['required', 'string', 'max:255'],
            'name.en'   => ['required', 'string', 'max:255'],

            'description'    => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],

            'is_active' => ['nullable', 'boolean'],

            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This program code is already taken.',
            'name.ar.required' => 'Arabic name is required.',
            'name.en.required' => 'English name is required.',
            'image.max' => 'Image size must not exceed 5MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Accept flexible payloads from mobile:
        // 1) name_ar / name_en  -> name[ar]/name[en]
        // 2) description_ar / description_en -> description[ar]/description[en]
        // 3) name / description could arrive as JSON string -> decode
        $input = $this->all();

        // decode JSON strings if sent like: "name": "{\"ar\":\"..\",\"en\":\"..\"}"
        foreach (['name', 'description'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $decoded = json_decode($input[$field], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $input[$field] = $decoded;
                }
            }
        }

        // map flat keys -> nested arrays
        if (!isset($input['name']) || !is_array($input['name'])) {
            $input['name'] = [];
        }
        if ($this->filled('name_ar')) {
            $input['name']['ar'] = $this->input('name_ar');
        }
        if ($this->filled('name_en')) {
            $input['name']['en'] = $this->input('name_en');
        }

        if (!isset($input['description']) || !is_array($input['description'])) {
            $input['description'] = [];
        }
        if ($this->has('description_ar')) {
            $input['description']['ar'] = $this->input('description_ar');
        }
        if ($this->has('description_en')) {
            $input['description']['en'] = $this->input('description_en');
        }

        // normalize boolean (Flutter sometimes sends "0"/"1"/true/false)
        if ($this->has('is_active')) {
            $input['is_active'] = filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                ?? (bool) $this->input('is_active');
        }

        $this->replace($input);
    }
}
