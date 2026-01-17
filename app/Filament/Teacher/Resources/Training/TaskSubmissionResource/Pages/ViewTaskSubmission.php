<?php

namespace App\Filament\Teacher\Resources\Training\TaskSubmissionResource\Pages;

use App\Filament\Teacher\Resources\Training\TaskSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTaskSubmission extends ViewRecord
{
    protected static string $resource = TaskSubmissionResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->record->task->course->owner_teacher_id !== auth('teacher')->id()) {
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
