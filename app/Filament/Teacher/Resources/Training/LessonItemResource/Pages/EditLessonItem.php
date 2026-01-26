<?php

namespace App\Filament\Teacher\Resources\Training\LessonItemResource\Pages;

use App\Domain\Media\Models\MediaFile;
use App\Domain\Training\Models\LessonItem;
use App\Filament\Teacher\Resources\Training\LessonItemResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EditLessonItem extends EditRecord
{
    protected static string $resource = LessonItemResource::class;

    protected function resolveRecord(int | string $key): Model
    {
        $teacherId = auth('teacher')->id();
        abort_if(!$teacherId, 403);

        $record = static::getResource()::getEloquentQuery()
            ->whereKey($key)
            ->with(['lesson.section.course'])
            ->first();

        abort_if(!$record, 404);

        return $record;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $teacherId = auth('teacher')->id();
        abort_if(!$teacherId, 403);

        if (($data['type'] ?? null) === 'link') {
            $data['media_file_id'] = null;
            unset($data['media_upload']);
            return $data;
        }

        if (!empty($data['media_upload'])) {
            $disk = 'local';
            $path = is_array($data['media_upload']) ? $data['media_upload'][0] : $data['media_upload'];

            if (!Storage::disk($disk)->exists($path)) {
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

            $fileUrl = Storage::disk($disk)->url($path);

            $data['media_file_id'] = $media->id;
            $data['external_url'] = $fileUrl;
            unset($data['media_upload']);
        }

        return $data;
    }
}
