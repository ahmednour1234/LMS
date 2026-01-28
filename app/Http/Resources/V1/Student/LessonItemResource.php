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

        $mediaFile = ($this->media_file_id && $this->relationLoaded('mediaFile'))
            ? $this->mediaFile
            : null;

        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'title'        => $this->getTranslatedValue($this->title, $locale),
            'external_url' => $this->external_url,
            'order'        => $this->order,

            'media_file'   => $this->when($mediaFile, function () use ($mediaFile) {
                $disk = $mediaFile->disk ?: 'local';

                // ✅ المطلوب: اعتمد على path column
                $path = $mediaFile->path ?: $mediaFile->filename;

                $url = null;

                if ($path) {
                    try {
                        // لو الملف موجود فعلًا على الديسك
                        if (Storage::disk($disk)->exists($path)) {
                            if ($disk === 'public') {
                                // public disk => URL مباشر
                                $url = Storage::disk($disk)->url($path);
                            } else {
                                // private/local/s3... => endpoint للتحميل
                                $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');
                                $url = $baseUrl . '/public/api/v1/student/media/' . $mediaFile->id . '/download';
                            }
                        }
                    } catch (\Throwable $e) {
                        $url = null;
                    }
                }

                return [
                    'id'                => $mediaFile->id,
                    'path'              => $path,
                    'disk'              => $disk,
                    'url'               => $url,
                    'mime_type'         => $mediaFile->mime_type,
                    'original_filename' => $mediaFile->original_filename,
                    'size'              => $mediaFile->size,
                ];
            }),
        ];
    }
}
