<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @bodyParam program_id integer required Program id. Example: 2
 * @bodyParam code string required Course code (unique). Example: CRS-100
 * @bodyParam name object required Course name translations.
 * @bodyParam name.ar string required Arabic name. Example: دورة لارافيل
 * @bodyParam name.en string required English name. Example: Laravel Course
 * @bodyParam description object optional Course description translations.
 * @bodyParam description.ar string optional Arabic description.
 * @bodyParam description.en string optional English description.
 * @bodyParam image file optional Course image (jpg,png,webp) max 5MB.
 * @bodyParam delivery_type string required Delivery type (onsite, online, hybrid). Example: online
 * @bodyParam duration_hours numeric optional Duration in hours. Example: 12
 * @bodyParam is_active boolean optional Active status (default true). Example: 1
 *
 * @bodyParam pricing object optional Pricing payload (for selected delivery_type).
 * @bodyParam pricing.pricing_mode string optional course_total|per_session|both. Example: course_total
 * @bodyParam pricing.price numeric optional Total course price. Example: 150.000
 * @bodyParam pricing.session_price numeric optional Session price. Example: 10.000
 * @bodyParam pricing.sessions_count integer optional Sessions count. Example: 15
 * @bodyParam pricing.allow_installments boolean optional Allow installments. Example: 1
 * @bodyParam pricing.min_down_payment numeric optional Minimum down payment. Example: 50.000
 * @bodyParam pricing.max_installments integer optional Maximum installments. Example: 6
 * @bodyParam pricing.is_active boolean optional Pricing active. Example: 1
 */
class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth:teacher middleware
    }

    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')],
            'code'       => ['required', 'string', 'max:50', Rule::unique('courses', 'code')],

            'name'       => ['required', 'array'],
            'name.ar'    => ['required', 'string', 'max:255'],
            'name.en'    => ['required', 'string', 'max:255'],

            'description'    => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],

            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'delivery_type' => ['required', 'string', Rule::in(['onsite', 'online', 'hybrid'])],
            'duration_hours'=> ['nullable', 'numeric', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],

            // pricing (optional)
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

        // decode possible json strings
        foreach (['name', 'description', 'pricing'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $decoded = json_decode($input[$field], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $input[$field] = $decoded;
                }
            }
        }

        // map flat keys -> nested translations
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

        // normalize booleans
        foreach (['is_active'] as $boolField) {
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
