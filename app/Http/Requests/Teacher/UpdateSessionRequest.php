<?php

namespace App\Http\Requests\Teacher;

use App\Domain\Training\Enums\SessionLocationType;
use App\Domain\Training\Enums\SessionProvider;
use App\Domain\Training\Enums\SessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSessionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // course_id ممنوع تغييره هنا (سيكيوريتي + منطق)، خليه ثابت
            'lesson_id' => ['nullable', 'integer', 'min:1'],

            'title' => ['sometimes', 'array'],
            'title.ar' => ['sometimes', 'string', 'max:255'],
            'title.en' => ['sometimes', 'string', 'max:255'],

            'location_type' => ['sometimes', Rule::in(array_column(SessionLocationType::cases(), 'value'))],
            'provider' => ['sometimes', 'nullable', Rule::in(array_column(SessionProvider::cases(), 'value'))],

            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],

            'status' => ['sometimes', Rule::in(array_column(SessionStatus::cases(), 'value'))],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('ends_at', ['after:starts_at'], function ($input) {
            return isset($input->starts_at) && isset($input->ends_at);
        });
    }
}
