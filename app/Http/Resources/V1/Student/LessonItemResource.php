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

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->getTranslatedValue($this->title, $locale),
            'external_url' => $this->external_url,
            'order' => $this->order,
            'media_file' => $this->when(
                $this->media_file_id && $this->relationLoaded('mediaFile') && $this->mediaFile,
                function () {
                    $disk = $this->mediaFile->disk ?? 'local';
                    $path = $this->mediaFile->path ?? $this->mediaFile->filename;

                    $url = null;
                    if ($path && Storage::disk($disk)->exists($path)) {
                        try {
                            if ($disk === 'public') {
                                $url = Storage::disk($disk)->url($path);
                            } else {
                                $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');
                                $mediaId = $this->mediaFile->id;
                                $url = $baseUrl . '/public/api/v1/student/media/' . $mediaId . '/download';
                            }
                        } catch (\Exception $e) {
                            $url = null;
                        }
                    }

                    return [
                        'id' => $this->mediaFile->id,
                        'url' => $url,
                        'mime_type' => $this->mediaFile->mime_type,
                        'original_filename' => $this->mediaFile->original_filename,
                        'size' => $this->mediaFile->size,
                    ];
                }
            ),
        ];
    }
}
