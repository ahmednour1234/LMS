<?php

namespace App\Support\Traits;

trait HasTranslatableFields
{
    /**
     * Get translated value from JSON field based on current locale.
     *
     * @param mixed $field The JSON field value (array or JSON string)
     * @param string|null $locale Optional locale override
     * @param mixed $fallback Fallback value if translation not found
     * @return mixed
     */
    protected function getTranslatedValue($field, ?string $locale = null, $fallback = null)
    {
        if ($field === null) {
            return $fallback;
        }

        $locale = $locale ?? app()->getLocale();
        $fallbackLocale = config('app.fallback_locale', 'en');

        // If field is already a string (not JSON), return as is
        if (is_string($field) && !str_starts_with(trim($field), '{') && !str_starts_with(trim($field), '[')) {
            return $field;
        }

        // Decode JSON if needed
        if (is_string($field)) {
            $decoded = json_decode($field, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $fallback ?? $field;
            }
            $field = $decoded;
        }

        // If not an array, return as is
        if (!is_array($field)) {
            return $fallback ?? $field;
        }

        // Try to get value for current locale
        if (isset($field[$locale])) {
            return $field[$locale];
        }

        // Try fallback locale
        if (isset($field[$fallbackLocale])) {
            return $field[$fallbackLocale];
        }

        // Try any available locale
        if (!empty($field)) {
            return reset($field);
        }

        return $fallback;
    }

    /**
     * Get all translations for a JSON field.
     *
     * @param mixed $field The JSON field value
     * @return array
     */
    protected function getAllTranslations($field): array
    {
        if ($field === null) {
            return [];
        }

        // Decode JSON if needed
        if (is_string($field)) {
            $decoded = json_decode($field, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
            $field = $decoded;
        }

        return is_array($field) ? $field : [];
    }
}

