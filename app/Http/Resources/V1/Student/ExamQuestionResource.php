<?php

namespace App\Http\Resources\V1\Student;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamQuestionResource extends JsonResource
{
    use HasTranslatableFields;

    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'type' => $this->type,
            'question' => $this->getTranslatedValue($this->question, $locale),
            'options' => $this->getTranslatedOptions($this->options, $locale),
            'points' => (float) $this->points,
            'order' => $this->order,
        ];
    }

    protected function getTranslatedOptions($options, string $locale): ?array
    {
        if (!is_array($options)) {
            return $options;
        }

        return array_map(function ($option) use ($locale) {
            return is_array($option) ? $this->getTranslatedValue($option, $locale) : $option;
        }, $options);
    }
}
