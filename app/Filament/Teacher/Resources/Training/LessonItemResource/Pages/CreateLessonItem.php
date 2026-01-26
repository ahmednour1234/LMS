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
        abort_if(!$teacherId, 403);

        $data['teacher_id'] = $teacherId;

        // لو Link: مفيش Media
        if (($data['type'] ?? null) === 'link') {
            $data['media_file_id'] = null;
            $data['external_url'] = $data['external_url'] ?? null;
            unset($data['media_upload']);
            return $data;
        }

        // Upload جديد
        if (!empty($data['media_upload'])) {
            $disk = 'local';
            $path = is_array($data['media_upload']) ? ($data['media_upload'][0] ?? null) : $data['media_upload'];

            if (!$path || !Storage::disk($disk)->exists($path)) {
                throw new \RuntimeException("Uploaded file not found on disk: {$disk}:{$path}");
            }

            $originalName = basename($path);
            $mime = Storage::disk($disk)->mimeType($path) ?? 'application/octet-stream';
            $size = Storage::disk($disk)->size($path) ?? 0;

            $media = MediaFile::create([
                'teacher_id' => $teacherId,
                'disk' => $disk,
                'path' => $path,
                'filename' => basename($path),
                'original_filename' => $originalName,
                'mime_type' => $mime,
                'size' => $size,
                'is_private' => true,
            ]);

            // ⚠️ لو disk local + private => url() غالبًا مش هينفع مباشرة
            // سيب external_url فاضي (أو اعمل route للتحميل)
            $data['media_file_id'] = $media->id;
            $data['external_url'] = $data['external_url'] ?? null;

            unset($data['media_upload']);
        }

        return $data;
    }
}
