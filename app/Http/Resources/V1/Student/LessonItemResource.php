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

            'media_file'   => $this->when($mediaFile, function () use ($mediaFile) {
                $disk = $mediaFile->disk ?: 'public';

                // ✅ اعتمد على عمود path في media_files
                $path = $mediaFile->path ?: $mediaFile->filename;

                $url = null;

                if ($path) {
                    try {
                        // تأكد إن الملف موجود (اختياري لكنه مفيد)
                        if (Storage::disk($disk)->exists($path)) {
                            $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');

                            // ✅ المطلوب: https://domain/public/storage/{path}
                            $url = $baseUrl . '/public/storage/' . ltrim($path, '/');
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
