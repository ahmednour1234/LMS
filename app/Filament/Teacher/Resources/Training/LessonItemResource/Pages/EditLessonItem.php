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
        
        if (isset($data['media_upload']) && $data['media_upload']) {
            $filePath = is_array($data['media_upload']) ? $data['media_upload'][0] : $data['media_upload'];
            
            if (Storage::disk('local')->exists($filePath)) {
                $originalName = basename($filePath);
                $mimeType = Storage::disk('local')->mimeType($filePath) ?: 'application/octet-stream';
                $size = Storage::disk('local')->size($filePath);
                
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
                
                $data['media_file_id'] = $mediaFile->id;
            }
        }
        
        if (!isset($data['teacher_id'])) {
            $data['teacher_id'] = $teacherId;
        }
        
        unset($data['media_upload']);
        
        return $data;
    }
}
