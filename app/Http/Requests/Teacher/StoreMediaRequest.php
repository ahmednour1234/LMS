<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam file file required
 * @bodyParam is_private boolean optional Example: 0
 */
class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:512000'], // 500MB
            'is_private' => ['nullable', 'boolean'],
        ];
    }
}
