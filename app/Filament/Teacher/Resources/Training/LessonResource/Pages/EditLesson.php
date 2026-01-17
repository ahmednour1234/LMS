<?php

namespace App\Filament\Teacher\Resources\Training\LessonResource\Pages;

use App\Filament\Teacher\Resources\Training\LessonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->record->section->course->owner_teacher_id !== auth('teacher')->id()) {
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
}
