<?php

namespace App\Filament\Teacher\Resources\Training\ExamResource\Pages;

use App\Filament\Teacher\Resources\Training\ExamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditExam extends EditRecord
{
    protected static string $resource = ExamResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->record->course->owner_teacher_id !== auth('teacher')->id()) {
            abort(404);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function afterSave(): void
    {
        $this->record->total_score = $this->record->computeTotalScore();
        $this->record->save();
    }
}
