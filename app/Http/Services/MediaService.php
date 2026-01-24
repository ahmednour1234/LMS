<?php

namespace App\Http\Services;

use App\Domain\Media\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    public function upload(UploadedFile $file, ?int $userId, bool $isPrivate = false, ?int $branchId = null, ?int $teacherId = null): MediaFile
    {
        $disk = 'public';
        $path = $file->store('media', $disk);

        return MediaFile::create([
            'filename' => basename($path),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'disk' => $disk,
            'path' => $path,
            'user_id' => $userId,
            'teacher_id' => $teacherId,
            'branch_id' => $branchId,
            'is_private' => $isPrivate,
            'access_token' => null,
            'expires_at' => null,
        ]);
    }
}
