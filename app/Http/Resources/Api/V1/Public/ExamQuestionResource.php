<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Support\Traits\HasTranslatableFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamQuestionResource extends JsonResource
{
    use HasTranslatableFields;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        $options = null;
        if ($this->options) {
            $optionsArray = is_array($this->options) ? $this->options : json_decode($this->options, true) ?? [];
            $options = array_map(function($option) use ($locale) {
                // If option is an object/array with a 'text' field, translate the text
                if (is_array($option) && isset($option['text'])) {
                    $option['text'] = $this->getTranslatedValue($option['text'], $locale);
                    return $option;
                }
                // If option itself is translatable (object with ar/en), translate it
                return $this->getTranslatedValue($option, $locale);
            }, $optionsArray);
        }

        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'type' => $this->type, // mcq/essay
            'question' => $this->getTranslatedValue($this->question, $locale),
            'options' => $options, // for mcq - translate each option
            'correct_answer' => $this->correct_answer,
            'points' => (float) $this->points,
            'order' => $this->order,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
