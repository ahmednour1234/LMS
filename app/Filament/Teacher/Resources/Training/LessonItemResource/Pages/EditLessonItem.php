<?php

namespace App\Filament\Teacher\Resources\Training\LessonItemResource\Pages;

use App\Domain\Media\Models\MediaFile;
use App\Filament\Teacher\Resources\Training\LessonItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditLessonItem extends EditRecord
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
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $teacherId = auth('teacher')->id();
        if (!isset($data['teacher_id'])) {
            $data['teacher_id'] = $teacherId;
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $teacherId = auth('teacher')->id();
        $formData = $this->form->getState();

        if (isset($formData['media_upload']) && $formData['media_upload']) {
            $filePath = is_array($formData['media_upload']) ? $formData['media_upload'][0] : $formData['media_upload'];

            if ($filePath) {
                try {
                    $disk = Storage::disk('local');

                    if (!$disk->exists($filePath)) {
                        return;
                    }

                    $originalName = basename($filePath);
                    $mimeType = 'application/octet-stream';
                    $size = 0;

                    try {
                        if ($disk->exists($filePath)) {
                            $mimeType = $disk->mimeType($filePath) ?: 'application/octet-stream';
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to get mime type for ' . $filePath . ': ' . $e->getMessage());
                    }

                    try {
                        if ($disk->exists($filePath)) {
                            $size = $disk->size($filePath);
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to get file size for ' . $filePath . ': ' . $e->getMessage());
                    }

                    $mediaFile = MediaFile::create([
                        'filename' => $filePath,
                        'original_filename' => $originalName,
                        'mime_type' => $mimeType,
                        'size' => $size,
                        'disk' => 'local',
                        'path' => $filePath,
                        'teacher_id' => $teacherId,
                        'is_private' => true,
                    ]);

                    $fileUrl = Storage::disk('local')->url($filePath);

                    $this->record->update([
                        'media_file_id' => $mediaFile->id,
                        'external_url' => $fileUrl
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to create MediaFile: ' . $e->getMessage());
                }
            }
        }
    }
}
