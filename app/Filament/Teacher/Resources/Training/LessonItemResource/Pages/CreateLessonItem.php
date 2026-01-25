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

        // لو type = link، امسح أي media
        if (($data['type'] ?? null) === 'link') {
            $data['media_file_id'] = null;
            unset($data['media_upload']);
            return $data;
        }

        // لو فيه upload جديد: media_upload بيكون path (string) على نفس disk
        if (!empty($data['media_upload'])) {
            $path = $data['media_upload']; // مثال: media/xxxx.pdf
            $disk = 'local';

            $originalName = basename($path);
            $mime = Storage::disk($disk)->mimeType($path);
            $size = Storage::disk($disk)->size($path);

            $media = MediaFile::create([
                'teacher_id'        => $teacherId,
                'disk'              => $disk,
                'path'              => $path,
                'filename'          => basename($path),
                'original_filename' => $originalName,
                'mime_type'         => $mime,
                'size'              => $size,
            ]);

            $data['media_file_id'] = $media->id;
            unset($data['media_upload']);
        }

        return $data;
    }
}
