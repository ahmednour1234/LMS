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

        // ✅ لا تعتمد على getEloquentQuery() الخاصة بالـ Resource (قد تكون راجعة Builder بدون Model)
        $record = LessonItem::query()
            ->whereKey($key)
            ->with(['lesson.section.course'])
            // ✅ تأكيد الملكية للمدرس (عدّل الأعمدة حسب تصميمك)
            ->whereHas('lesson.section.course', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId);
            })
            ->first();

        abort_if(!$record, 404);

        return $record;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $teacherId = auth('teacher')->id();
        abort_if(!$teacherId, 403);

        $type = $data['type'] ?? null;

        // ✅ لو link: امسح أي ميديا + اترك external_url كما هو (لو عندك input للرابط)
        if ($type === 'link') {
            $data['media_file_id'] = null;
            unset($data['media_upload']);
            return $data;
        }

        // ✅ لو Upload جديد -> أنشئ MediaFile وخزّن id في media_file_id
        if (!empty($data['media_upload'])) {
            // مهم: خلي الديسك مطابق للي FileUpload بيستخدمه في Filament
            // لو أنت فعلاً رافع على local اتركها، لكن كثير بيكون public.
            $disk = 'local';
            $path = $data['media_upload']; // مثال: media/xxxx

            if (!Storage::disk($disk)->exists($path)) {
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

            $data['media_file_id'] = $media->id;

            // اختياري: لو بتستخدم external_url لعرض الملف
            $data['external_url'] = Storage::disk($disk)->url($path);

            unset($data['media_upload']);
        }

        return $data;
    }
}
