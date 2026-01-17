<?php

namespace App\Filament\Teacher\Resources\Training\TaskSubmissionResource\Pages;

use App\Filament\Teacher\Resources\Training\TaskSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaskSubmission extends EditRecord
{
    protected static string $resource = TaskSubmissionResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->record->task->course->owner_teacher_id !== auth('teacher')->id()) {
            abort(404);
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!isset($data['reviewed_at']) && $data['status'] === 'reviewed') {
            $data['reviewed_at'] = now();
            $data['reviewed_by'] = auth('teacher')->id();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
