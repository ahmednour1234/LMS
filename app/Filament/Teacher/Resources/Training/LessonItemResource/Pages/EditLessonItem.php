<?php

namespace App\Filament\Teacher\Resources\Training\LessonItemResource\Pages;

use App\Domain\Media\Models\MediaFile;
use App\Filament\Teacher\Resources\Training\LessonItemResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EditLessonItem extends EditRecord
{
    protected static string $resource = LessonItemResource::class;

    /**
     * ✅ مهم: نضمن إن المدرّس شايف بس اللي بتاعه
     * وده كمان بيمنع مسارات Filament اللي ممكن تسبب hydrate() error
     */
    protected function resolveRecord(int|string $key): Model
    {
        $teacherId = auth('teacher')->id();
        abort_if(!$teacherId, 403);

        return static::getResource()::getEloquentQuery()
            ->whereKey($key)
            ->firstOrFail();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $teacherId = auth('teacher')->id();
        abort_if(!$teacherId, 403);

        $data['teacher_id'] = $teacherId;

        // لو Link
        if (($data['type'] ?? null) === 'link') {
            $data['media_file_id'] = null;
            // external_url لازم يبقى موجود
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

            // (اختياري) لو عايز تمنع تراكم ملفات قديمة:
            // لو عنده media_file_id قديم، امسح record القديم أو الملف حسب سياستك.

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

            $data['media_file_id'] = $media->id;
            unset($data['media_upload']);
        }

        return $data;
    }
}
