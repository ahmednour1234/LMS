<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MediaFileResource extends JsonResource
{
    public function toArray($request): array
    {
        $url = $this->disk && $this->path ? Storage::disk($this->disk)->url($this->path) : null;

        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'disk' => $this->disk,
            'path' => $this->path,
            'url' => $url,
            'is_private' => (bool) $this->is_private,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
