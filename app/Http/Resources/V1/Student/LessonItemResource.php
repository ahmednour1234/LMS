<?php

namespace App\Http\Resources\V1\Student;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'media_file' => $this->whenLoaded('mediaFile', function () {
                return [
                    'id' => $this->mediaFile->id,
                    'url' => $this->mediaFile->url ?? null,
                    'type' => $this->mediaFile->type ?? null,
                ];
            }),
        ];
    }
}
