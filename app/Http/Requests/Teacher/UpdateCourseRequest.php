<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @bodyParam program_id integer optional Program id. Example: 2
 * @bodyParam code string optional Course code (unique). Example: CRS-100
 * @bodyParam name object optional Course name translations.
 * @bodyParam name.ar string optional Arabic name.
 * @bodyParam name.en string optional English name.
 * @bodyParam description object optional Course description translations.
 * @bodyParam description.ar string optional Arabic description.
 * @bodyParam description.en string optional English description.
 * @bodyParam image file optional Course image (jpg,png,webp) max 5MB.
 * @bodyParam remove_image boolean optional Remove current image. Example: 1
 * @bodyParam delivery_type string optional Delivery type (onsite, online, hybrid). Example: online
 * @bodyParam duration_hours numeric optional Duration in hours. Example: 12
 * @bodyParam is_active boolean optional Active status. Example: 1
 *
 * @bodyParam pricing object optional Pricing payload (for course delivery_type).
 */
class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseId = (int) $this->route('course');

        return [
            'program_id' => ['sometimes', 'required', 'integer', Rule::exists('programs', 'id')],

            'code' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('courses', 'code')->ignore($courseId),
            ],

            'name'    => ['sometimes', 'required', 'array'],
            'name.ar' => ['sometimes', 'required', 'string', 'max:255'],
            'name.en' => ['sometimes', 'required', 'string', 'max:255'],

            'description'    => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],

            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],

            'delivery_type' => ['sometimes', 'required', 'string', Rule::in(['onsite', 'online', 'hybrid'])],
            'duration_hours'=> ['nullable', 'numeric', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],

            // pricing
            'pricing' => ['nullable', 'array'],
            'pricing.pricing_mode' => ['nullable', 'string', Rule::in(['course_total', 'per_session', 'both'])],
            'pricing.price' => ['nullable', 'numeric', 'min:0.001'],
            'pricing.session_price' => ['nullable', 'numeric', 'min:0.001'],
            'pricing.sessions_count' => ['nullable', 'integer', 'min:1'],
            'pricing.allow_installments' => ['nullable', 'boolean'],
            'pricing.min_down_payment' => ['nullable', 'numeric', 'min:0'],
            'pricing.max_installments' => ['nullable', 'integer', 'min:1'],
            'pricing.is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        foreach (['name', 'description', 'pricing'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $decoded = json_decode($input[$field], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $input[$field] = $decoded;
                }
            }
        }

        if ($this->hasAny(['name_ar', 'name_en'])) {
            $input['name'] = is_array($input['name'] ?? null) ? $input['name'] : [];
            if ($this->filled('name_ar')) $input['name']['ar'] = $this->input('name_ar');
            if ($this->filled('name_en')) $input['name']['en'] = $this->input('name_en');
        }

        if ($this->hasAny(['description_ar', 'description_en'])) {
            $input['description'] = is_array($input['description'] ?? null) ? $input['description'] : [];
            if ($this->has('description_ar')) $input['description']['ar'] = $this->input('description_ar');
            if ($this->has('description_en')) $input['description']['en'] = $this->input('description_en');
        }

        foreach (['is_active', 'remove_image'] as $boolField) {
            if ($this->has($boolField)) {
                $input[$boolField] = filter_var($this->input($boolField), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    ?? (bool) $this->input($boolField);
            }
        }

        if (isset($input['pricing']) && is_array($input['pricing'])) {
            foreach (['allow_installments', 'is_active'] as $boolField) {
                if (array_key_exists($boolField, $input['pricing'])) {
                    $input['pricing'][$boolField] = filter_var($input['pricing'][$boolField], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                        ?? (bool) $input['pricing'][$boolField];
                }
            }
        }

        $this->replace($input);
    }
}
