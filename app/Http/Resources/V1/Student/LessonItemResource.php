<?php

namespace App\Http\Resources\V1\Student;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LessonItemResource extends JsonResource
{
    use HasTranslatableFields;

    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        $mediaFile = ($this->media_file_id && $this->relationLoaded('mediaFile') && $this->mediaFile)
            ? $this->mediaFile
            : null;

        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'title'        => $this->getTranslatedValue($this->title, $locale),
            'external_url' => $this->external_url,
            'order'        => $this->order,

            'media_file' => $this->when($mediaFile, function () use ($mediaFile) {
                $disk = $mediaFile->disk ?: 'public';

                // ✅ path من جدول media_files
                $path = $mediaFile->path ?: $mediaFile->filename;

                $url = null;

                if (!empty($path)) {
                    try {
                        // (اختياري) تحقق وجود الملف
                        if (Storage::disk($disk)->exists($path)) {
                            $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');

                            // ✅ المطلوب: /public/storage/app/public/{path}
                            // - نشيل أي prefix ممكن يكون داخل path علشان مايتكرر
                            $cleanPath = ltrim($path, '/');
                            $cleanPath = preg_replace('#^(public/|storage/|app/public/)#', '', $cleanPath);

                            $url = $baseUrl . '/storage/app/public/' . $cleanPath;
                        }
                    } catch (\Throwable $e) {
                        $url = null;
                    }
                }

                return [
                    'id'                => $mediaFile->id,
                    'disk'              => $disk,
                    'path'              => $path,
                    'url'               => $url,
                    'mime_type'         => $mediaFile->mime_type,
                    'original_filename' => $mediaFile->original_filename,
                    'size'              => $mediaFile->size,
                ];
            }),
        ];
    }
}
