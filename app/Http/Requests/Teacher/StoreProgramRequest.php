<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // محمي بالـ middleware أصلاً
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:programs,id'],
            'code'      => ['required', 'string', 'max:50', 'unique:programs,code'],
            'name.ar'   => ['required', 'string', 'max:255'],
            'name.en'   => ['required', 'string', 'max:255'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // لو جالك name/description كـ string من الموبايل ممكن تبقى محتاج normalize
        // سيبناها بسيطة حالياً
    }
}
