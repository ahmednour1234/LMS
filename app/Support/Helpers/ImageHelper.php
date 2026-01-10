<?php

namespace App\Support\Helpers;

use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    public static function getImageUrl($imagePath, ?string $locale = null): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        if (is_array($imagePath)) {
            $locale = $locale ?? app()->getLocale();
            $imagePath = $imagePath[$locale] ?? $imagePath['ar'] ?? $imagePath['en'] ?? null;
            
            if (empty($imagePath)) {
                return null;
            }
        }

        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }

        if (Storage::disk('public')->exists($imagePath)) {
            return Storage::disk('public')->url($imagePath);
        }

        return null;
    }

    public static function getFullImageUrl($imagePath, ?string $locale = null): ?string
    {
        $url = self::getImageUrl($imagePath, $locale);
        
        if ($url && !filter_var($url, FILTER_VALIDATE_URL)) {
            return url($url);
        }

        return $url;
    }
}

