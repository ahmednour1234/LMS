<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * @bodyParam program_id integer required Example: 2
 * @bodyParam code string required Example: CRS-100
 *
 * @bodyParam name object required
 * @bodyParam name.ar string required Example: دورة
 * @bodyParam name.en string required Example: Course
 *
 * @bodyParam description object optional
 * @bodyParam description.ar string optional Example: وصف عربي
 * @bodyParam description.en string optional Example: English description
 *
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
    public function authorize(): bool
    {
        return true;
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

            'delivery_type'  => ['required', 'string', Rule::in(['onsite', 'online', 'hybrid'])],
            'duration_hours' => ['nullable', 'numeric', 'min:0'],
            'is_active'      => ['nullable', 'boolean'],

            // multi prices
            'prices' => ['nullable', 'array', 'min:1'],
            'prices.*' => ['array'],

            'prices.*.delivery_type' => ['required', 'string', Rule::in(['onsite', 'online', 'hybrid'])],
            'prices.*.pricing_mode'  => ['nullable', 'string', Rule::in(['course_total', 'per_session', 'both'])],

            'prices.*.price'          => ['nullable', 'numeric', 'min:0.001'],
            'prices.*.session_price'  => ['nullable', 'numeric', 'min:0.001'],
            'prices.*.sessions_count' => ['nullable', 'integer', 'min:1'],

            'prices.*.allow_installments' => ['nullable', 'boolean'],
            'prices.*.min_down_payment'   => ['nullable', 'numeric', 'min:0'],
            'prices.*.max_installments'   => ['nullable', 'integer', 'min:1'],
            'prices.*.is_active'          => ['nullable', 'boolean'],
        ];
    }

    /**
     * Cross-field validation (business rules)
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $prices = $this->input('prices');

            if (!is_array($prices) || empty($prices)) {
                return;
            }

            // 1) prevent duplicates delivery_type
            $types = array_values(array_filter(array_map(fn ($p) => $p['delivery_type'] ?? null, $prices)));
            if (count($types) !== count(array_unique($types))) {
                $v->errors()->add('prices', 'prices delivery_type must be unique (no duplicates).');
            }

            // 2) validate pricing_mode requirements
            foreach ($prices as $i => $p) {
                if (!is_array($p)) continue;

                $mode = $p['pricing_mode'] ?? 'course_total';

                $hasCourseTotal = in_array($mode, ['course_total', 'both'], true);
                $hasPerSession  = in_array($mode, ['per_session', 'both'], true);

                if ($hasCourseTotal) {
                    $price = (float) ($p['price'] ?? 0);
                    if ($price <= 0) {
                        $v->errors()->add("prices.$i.price", 'price is required when pricing_mode is course_total or both.');
                    }
                }

                if ($hasPerSession) {
                    $sp = (float) ($p['session_price'] ?? 0);
                    $sc = (int) ($p['sessions_count'] ?? 0);

                    if ($sp <= 0) {
                        $v->errors()->add("prices.$i.session_price", 'session_price is required when pricing_mode is per_session or both.');
                    }
                    if ($sc < 1) {
                        $v->errors()->add("prices.$i.sessions_count", 'sessions_count is required when pricing_mode is per_session or both.');
                    }
                }

                // 3) installments rules
                $allowInstallments = (bool) ($p['allow_installments'] ?? false);
                if ($allowInstallments) {
                    $maxInstallments = (int) ($p['max_installments'] ?? 0);
                    if ($maxInstallments < 1) {
                        $v->errors()->add("prices.$i.max_installments", 'max_installments is required when allow_installments is true.');
                    }

                    // min_down_payment cannot exceed price (only meaningful when course_total/both)
                    $minDown = (float) ($p['min_down_payment'] ?? 0);
                    $price   = (float) ($p['price'] ?? 0);

                    if ($minDown > 0 && $price > 0 && $minDown > $price) {
                        $v->errors()->add("prices.$i.min_down_payment", 'min_down_payment cannot exceed price.');
                    }

                    // if per_session only -> installments not allowed
                    if (($p['pricing_mode'] ?? 'course_total') === 'per_session') {
                        $v->errors()->add("prices.$i.allow_installments", 'Installments are not allowed when pricing_mode is per_session.');
                    }

                    // if both/per_session -> max_installments should not exceed sessions_count (when sessions_count exists)
                    if (in_array(($p['pricing_mode'] ?? 'course_total'), ['per_session', 'both'], true)) {
                        $sessionsCount = (int) ($p['sessions_count'] ?? 0);
                        if ($sessionsCount > 0 && $maxInstallments > $sessionsCount) {
                            $v->errors()->add("prices.$i.max_installments", "max_installments cannot exceed sessions_count ($sessionsCount).");
                        }
                    }
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        // decode json strings from mobile (multipart/form-data)
        foreach (['name', 'description', 'prices'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $decoded = json_decode($input[$field], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $input[$field] = $decoded;
                }
            }
        }

        // flat translations support (name_ar/name_en)
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
            if (array_key_exists($b, $input)) {
                $input[$b] = filter_var($input[$b], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $input[$b];
            }
        }

        if (isset($input['prices']) && is_array($input['prices'])) {
            foreach ($input['prices'] as $i => $p) {
                if (!is_array($p)) continue;

                foreach (['allow_installments', 'is_active'] as $b) {
                    if (array_key_exists($b, $p)) {
                        $input['prices'][$i][$b] = filter_var($p[$b], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $p[$b];
                    }
                }
            }
        }

        $this->replace($input);
    }
}
