<?php

namespace App\Filament\Teacher\Resources\Training\ExamResource\Pages;

use App\Filament\Teacher\Resources\Training\ExamResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate that each question has at least one language (ar or en)
        if (isset($data['questions']) && is_array($data['questions'])) {
            foreach ($data['questions'] as $index => $question) {
                // Handle both array format and object format
                $questionData = is_array($question) ? $question : (array) $question;
                $questionField = $questionData['question'] ?? [];
                $arValue = trim((string) (is_array($questionField) ? ($questionField['ar'] ?? '') : ''));
                $enValue = trim((string) (is_array($questionField) ? ($questionField['en'] ?? '') : ''));
                
                if (empty($arValue) && empty($enValue)) {
                    throw ValidationException::withMessages([
                        "questions.{$index}.question.ar" => [__('exams.question_at_least_one_required')],
                        "questions.{$index}.question.en" => [__('exams.question_at_least_one_required')],
                    ]);
                }
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->total_score = $this->record->computeTotalScore();
        $this->record->save();
    }
}
