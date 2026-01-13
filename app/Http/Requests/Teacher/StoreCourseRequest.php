<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @bodyParam program_id integer required Example: 2
 * @bodyParam code string required Example: CRS-100
 * @bodyParam name object required
 * @bodyParam name.ar string required Example: دورة
 * @bodyParam name.en string required Example: Course
 * @bodyParam description object optional
 * @bodyParam description.ar string optional
 * @bodyParam description.en string optional
 * @bodyParam image file optional
 * @bodyParam delivery_type string required (onsite, online, hybrid) Example: hybrid
 * @bodyParam duration_hours numeric optional Example: 12
 * @bodyParam is_active boolean optional Example: 1
 *
 * @bodyParam prices array optional Array of prices (multi delivery types).
 * @bodyParam prices.0.delivery_type string required (online/onsite/hybrid). Example: online
 * @bodyParam prices.0.pricing_mode string optional course_total|per_session|both. Example: course_total
 * @bodyParam prices.0.price numeric optional Example: 150
 * @bodyParam prices.0.session_price numeric optional Example: 10
 * @bodyParam prices.0.sessions_count integer optional Example: 15
 * @bodyParam prices.0.allow_installments boolean optional Example: 1
 * @bodyParam prices.0.min_down_payment numeric optional Example: 50
 * @bodyParam prices.0.max_installments integer optional Example: 6
 * @bodyParam prices.0.is_active boolean optional Example: 1
 *
 * @bodyParam prices.1.delivery_type string required Example: onsite
 * @bodyParam prices.1.price numeric optional Example: 200
 */
class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

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

            // multi prices
            'prices' => ['nullable', 'array', 'min:1'],
            'prices.*.delivery_type' => ['required_with:prices', 'string', Rule::in(['onsite', 'online', 'hybrid'])],
            'prices.*.pricing_mode' => ['nullable', 'string', Rule::in(['course_total', 'per_session', 'both'])],

            'prices.*.price' => ['nullable', 'numeric', 'min:0.001'],
            'prices.*.session_price' => ['nullable', 'numeric', 'min:0.001'],
            'prices.*.sessions_count' => ['nullable', 'integer', 'min:1'],

            'prices.*.allow_installments' => ['nullable', 'boolean'],
            'prices.*.min_down_payment' => ['nullable', 'numeric', 'min:0'],
            'prices.*.max_installments' => ['nullable', 'integer', 'min:1'],
            'prices.*.is_active' => ['nullable', 'boolean'],

            // prevent duplicates delivery_type in same request
            'prices' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    if (!is_array($value)) return;
                    $types = array_map(fn($x) => $x['delivery_type'] ?? null, $value);
                    $types = array_filter($types);
                    if (count($types) !== count(array_unique($types))) {
                        $fail('prices delivery_type must be unique (no duplicates).');
                    }
                }
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        foreach (['name', 'description', 'prices'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $decoded = json_decode($input[$field], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $input[$field] = $decoded;
                }
            }
        }

        // flat translations support
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
        foreach (['is_active'] as $b) {
            if ($this->has($b)) {
                $input[$b] = filter_var($this->input($b), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    ?? (bool) $this->input($b);
            }
        }

        if (isset($input['prices']) && is_array($input['prices'])) {
            foreach ($input['prices'] as $i => $p) {
                if (!is_array($p)) continue;
                foreach (['allow_installments', 'is_active'] as $b) {
                    if (array_key_exists($b, $p)) {
                        $input['prices'][$i][$b] = filter_var($p[$b], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                            ?? (bool) $p[$b];
                    }
                }
            }
        }

        $this->replace($input);
    }
}
