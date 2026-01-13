<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'type' => $this->type, // video/pdf/file/link
            'title' => $this->title,
            'media_file_id' => $this->media_file_id,
            'external_url' => $this->external_url,
            'order' => $this->order,
            'is_active' => (bool) $this->is_active,

            'media' => $this->whenLoaded('mediaFile', function () {
                return [
                    'id' => $this->mediaFile->id,
                    'filename' => $this->mediaFile->filename,
                    'original_filename' => $this->mediaFile->original_filename,
                    'mime_type' => $this->mediaFile->mime_type,
                    'size' => $this->mediaFile->size,
                    'disk' => $this->mediaFile->disk,
                    'path' => $this->mediaFile->path,
                    'url' => method_exists($this->mediaFile, 'url')
                        ? $this->mediaFile->url()
                        : null,
                ];
            }),

            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
