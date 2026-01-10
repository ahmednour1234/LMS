<?php

namespace App\Support\Helpers;

use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    public static function getImageUrl(?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }

        if (Storage::disk('public')->exists($imagePath)) {
            return Storage::disk('public')->url($imagePath);
        }

        return null;
    }

    public static function getFullImageUrl(?string $imagePath): ?string
    {
        $url = self::getImageUrl($imagePath);
        
        if ($url && !filter_var($url, FILTER_VALIDATE_URL)) {
            return url($url);
        }

        return $url;
    }
}

