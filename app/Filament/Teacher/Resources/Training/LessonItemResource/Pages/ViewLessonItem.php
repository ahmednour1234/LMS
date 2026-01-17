<?php

namespace App\Filament\Teacher\Resources\Training\LessonItemResource\Pages;

use App\Filament\Teacher\Resources\Training\LessonItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLessonItem extends ViewRecord
{
    protected static string $resource = LessonItemResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->record->lesson->section->course->owner_teacher_id !== auth('teacher')->id()) {
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
