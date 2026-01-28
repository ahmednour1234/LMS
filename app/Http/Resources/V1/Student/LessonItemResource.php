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
                $disk = $mediaFile->disk ?: 'public';

                // ✅ المطلوب: url مبني على path column
                $path = $mediaFile->path ?: $mediaFile->filename;

                $url = null;

                if ($path) {
                    try {
                        // لو الملف موجود
                        if (Storage::disk($disk)->exists($path)) {

                            // 1) لو public disk => نطلع url مباشر من path
                            if ($disk === 'public') {
                                // Storage::url يرجع غالباً /storage/....
                                $relative = Storage::disk('public')->url($path);

                                $baseUrl = rtrim(config('app.url', ''), '/');
                                $url = $baseUrl . $relative; // يبقى: https://domain.com/storage/...
                            } else {
                                /**
                                 * 2) لو disk مش public:
                                 * - حاول تعمل url بنفسك لو الديسك مربوط لمجلد public عندك
                                 * - أو ارجع null (أفضل من رابط غلط)
                                 */
                                $baseUrl = rtrim(config('app.url', ''), '/');

                                // مثال شائع لو انت حاطط الملفات جوه public/storage يدويًا:
                                // $url = $baseUrl . '/public/storage/' . ltrim($path, '/');

                                // الافضل: ما نكذبش ونطلع رابط غلط
                                $url = null;
                            }
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
