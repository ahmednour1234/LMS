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

    protected function resolveRecord(int|string $key): Model
    {
        $teacherId = auth('teacher')->id();
        abort_if(!$teacherId, 403);

        return LessonItem::query()
            ->whereHas('lesson.section.course', fn ($q) => $q->where('owner_teacher_id', $teacherId))
            ->whereKey($key)
            ->firstOrFail();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $teacherId = auth('teacher')->id();
        abort_if(!$teacherId, 403);

        $data['teacher_id'] = $teacherId;

        if (($data['type'] ?? null) === 'link') {
            $data['media_file_id'] = null;
            unset($data['media_upload']);
            return $data;
        }

        // ✅ لو رفع جديد: اعمل MediaFile وقت الحفظ واستبدل القديم
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

            $data['media_file_id'] = $media->id;
            unset($data['media_upload']);
        } else {
            if (($data['media_file_id'] ?? null) === '__uploaded__') {
                $data['media_file_id'] = $this->record->media_file_id; // يرجع القديم بدل placeholder
            }
        }

        return $data;
    }
}
