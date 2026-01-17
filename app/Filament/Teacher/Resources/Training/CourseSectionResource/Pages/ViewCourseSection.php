<?php

namespace App\Filament\Teacher\Resources\Training\CourseSectionResource\Pages;

use App\Filament\Teacher\Resources\Training\CourseSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCourseSection extends ViewRecord
{
    protected static string $resource = CourseSectionResource::class;

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
