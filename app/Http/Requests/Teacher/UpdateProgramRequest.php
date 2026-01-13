<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $programId = (int) $this->route('program');

        return [
            'parent_id' => ['nullable', 'integer', 'exists:programs,id'],
            'code'      => ['sometimes', 'required', 'string', 'max:50', "unique:programs,code,{$programId}"],
            'name.ar'   => ['sometimes', 'required', 'string', 'max:255'],
            'name.en'   => ['sometimes', 'required', 'string', 'max:255'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }
}
