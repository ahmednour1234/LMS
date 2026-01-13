<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewTaskSubmissionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    /**body
     * @bodyParam score numeric required Example: 10
     * @bodyParam feedback object required
     * @bodyParam feedback.ar string required Example: Good work!
     * @bodyParam feedback.en string required Example: Good work!
     * @bodyParam status string required Example: reviewed
     */
    public function rules(): array
    {
        return [
            'score' => ['nullable', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'array'],
            'feedback.ar' => ['nullable', 'string'],
            'feedback.en' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['pending','submitted','reviewed','rejected'])],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    /**body
     * @bodyParam score numeric required Example: 10
     * @bodyParam feedback object required
     * @bodyParam feedback.ar string required Example: Good work!
     * @bodyParam feedback.en string required Example: Good work!
     * @bodyParam status string required Example: reviewed
     */
    protected function prepareForValidation(): void
    {
        $input = $this->all();

        if (isset($input['feedback']) && is_string($input['feedback'])) {
            $decoded = json_decode($input['feedback'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $input['feedback'] = $decoded;
            }
        }

        $this->replace($input);
    }
}
