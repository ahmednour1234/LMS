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
            Actions\Action::make('download_file')
                ->label(__('task_submissions.download_file'))
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => $this->record->mediaFile)
                ->url(fn () => $this->getMediaFileUrl())
                ->openUrlInNewTab(),
            Actions\EditAction::make(),
        ];
    }

    protected function getMediaFileUrl(): string
    {
        $mediaFile = $this->record->mediaFile;
        if (!$mediaFile) {
            return '#';
        }

        $disk = $mediaFile->disk ?? 'local';

        if ($disk === 'public') {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($mediaFile->path);
        } elseif ($disk === 's3') {
            return \Illuminate\Support\Facades\Storage::disk('s3')->url($mediaFile->path);
        }

        return route('filament.teacher.resources.training.task-submissions.download-file', ['record' => $this->record->id]);
    }
}
