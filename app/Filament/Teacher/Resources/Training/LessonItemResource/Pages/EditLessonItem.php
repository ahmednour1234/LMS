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

        if (!$this->record) {
            abort(404);
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $teacherId = auth('teacher')->id();

        if (($data['type'] ?? null) === 'link') {
            $data['media_file_id'] = null;
            unset($data['media_upload']);
            return $data;
        }

        if (!empty($data['media_upload'])) {
            $path = $data['media_upload'];
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

            $fileUrl = Storage::disk($disk)->url($path);

            $data['media_file_id'] = $media->id;
            $data['external_url'] = $fileUrl;
            unset($data['media_upload']);
        }

        return $data;
    }
}
