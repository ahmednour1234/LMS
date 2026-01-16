<?php

namespace App\Http\Requests\Teacher;

use App\Domain\Training\Enums\SessionLocationType;
use App\Domain\Training\Enums\SessionProvider;
use App\Domain\Training\Enums\SessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'min:1'],
            'lesson_id' => ['nullable', 'integer', 'min:1'],
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],

            'location_type' => ['required', Rule::in(array_column(SessionLocationType::cases(), 'value'))],
            'provider' => ['nullable', Rule::in(array_column(SessionProvider::cases(), 'value'))],

            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],

            'status' => ['required', Rule::in(array_column(SessionStatus::cases(), 'value'))],
        ];
    }
}
