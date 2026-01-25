<?php

namespace App\Filament\Teacher\Resources\Training\LessonItemResource\Pages;

use App\Domain\Media\Models\MediaFile;
use App\Filament\Teacher\Resources\Training\LessonItemResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditLessonItem extends EditRecord
{
    protected static string $resource = LessonItemResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $teacherId = auth('teacher')->id();
        abort_if(!$teacherId, 403);

        $this->record->loadMissing('lesson.section.course');

        if ($this->record->lesson->section->course->owner_teacher_id !== $teacherId) {
            abort(404);
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $teacherId = auth('teacher')->id();
        abort_if(!$teacherId, 403);

        // لو link: امسح أي ميديا
        if (($data['type'] ?? null) === 'link') {
            $data['media_file_id'] = null;
            unset($data['media_upload']);
            return $data;
        }

        // لو Upload جديد -> أنشئ MediaFile وخزّن id في media_file_id
        if (!empty($data['media_upload'])) {
            $disk = 'local';
            $path = $data['media_upload']; // media/xxxx

            // حماية بسيطة: تأكد الملف موجود
            if (!Storage::disk($disk)->exists($path)) {
                // لو الملف مش موجود لأي سبب (cleanup/temporary)، سيبها تفشل برسالة واضحة
                throw new \RuntimeException("Uploaded file not found on disk: {$disk}:{$path}");
            }

            $originalName = basename($path);
            $mime = Storage::disk($disk)->mimeType($path) ?? 'application/octet-stream';
            $size = Storage::disk($disk)->size($path) ?? 0;

            $media = MediaFile::create([
                'teacher_id'        => $teacherId,
                'disk'              => $disk,
                'path'              => $path,
                'filename'          => basename($path),
                'original_filename' => $originalName,
                'mime_type'         => $mime,
                'size'              => $size,
            ]);

            $fileUrl = Storage::disk($disk)->url($path);

            $data['media_file_id'] = $media->id;
            $data['external_url'] = $fileUrl;
            unset($data['media_upload']);
        }

        return $data;
    }
}
