<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\StoreMediaRequest;
use App\Http\Resources\Api\V1\Public\MediaFileResource;
use App\Http\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Teacher - Media
 */
class TeacherMediaController extends ApiController
{
    public function __construct(protected MediaService $mediaService) {}

    /**
     * Upload media
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $teacher = Auth::guard('teacher-api')->user();

        $file = $request->file('file');
        $isPrivate = (bool) ($request->input('is_private', false));

        // Use teacher_id, set user_id to null
        $media = $this->mediaService->upload(
            file: $file,
            userId: null,
            isPrivate: $isPrivate,
            teacherId: $teacher->id
        );

        return $this->successResponse(new MediaFileResource($media), 'Uploaded', 201);
    }
}
