<?php

namespace App\Filament\Teacher\Resources\Training\ExamResource\Pages;

use App\Filament\Teacher\Resources\Training\ExamResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExam extends ViewRecord
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
            Actions\EditAction::make(),
        ];
    }
}
