<?php

namespace App\Filament\Teacher\Resources\Training\ExamQuestionResource\Pages;

use App\Filament\Teacher\Resources\Training\ExamQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExamQuestion extends ViewRecord
{
    protected static string $resource = ExamQuestionResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->record->exam->course->owner_teacher_id !== auth('teacher')->id()) {
            abort(404);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
