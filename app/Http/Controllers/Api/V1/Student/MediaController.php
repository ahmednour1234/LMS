<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Domain\Media\Models\MediaFile;
use App\Domain\Training\Models\LessonItem;
use App\Domain\Enrollment\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class MediaController extends ApiController
{
    public function download(int $media): Response|JsonResponse
    {
        $student = auth('students')->user();
        
        $mediaFile = MediaFile::find($media);
        
        if (!$mediaFile) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Media file not found.',
                null,
                404
            );
        }
        
        $lessonItem = LessonItem::where('media_file_id', $mediaFile->id)
            ->where('is_active', true)
            ->whereHas('lesson', function ($query) {
                $query->where('is_active', true);
            })
            ->whereHas('lesson.section.course', function ($query) {
                $query->where('is_active', true);
            })
            ->first();
        
        if (!$lessonItem) {
            return $this->errorResponse(
                ApiErrorCode::FORBIDDEN,
                'Access denied.',
                null,
                403
            );
        }
        
        $course = $lessonItem->lesson->section->course;
        
        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'pending', 'pending_payment'])
            ->first();
        
        if (!$enrollment) {
            return $this->errorResponse(
                ApiErrorCode::FORBIDDEN,
                'You are not enrolled in this course.',
                null,
                403
            );
        }
        
        $disk = $mediaFile->disk ?? 'local';
        $path = $mediaFile->path ?? $mediaFile->filename;
        
        if (!$path || !Storage::disk($disk)->exists($path)) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'File not found.',
                null,
                404
            );
        }
        
        return Storage::disk($disk)->download(
            $path,
            $mediaFile->original_filename ?? basename($path)
        );
    }
}
