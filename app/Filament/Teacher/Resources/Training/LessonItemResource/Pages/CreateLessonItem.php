<?php

namespace App\Filament\Teacher\Resources\Training\LessonItemResource\Pages;

use App\Domain\Media\Models\MediaFile;
use App\Filament\Teacher\Resources\Training\LessonItemResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateLessonItem extends CreateRecord
{
    protected static string $resource = LessonItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
        
        $data['teacher_id'] = $teacherId;
        unset($data['media_upload']);
        
        return $data;
    }
}
