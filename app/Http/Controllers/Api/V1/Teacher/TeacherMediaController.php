<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\StoreMediaRequest;
use App\Http\Resources\Public\MediaFileResource;
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
        $teacher = Auth::guard('teacher')->user();

        $file = $request->file('file');
        $isPrivate = (bool) ($request->input('is_private', false));

        // teacher isn't User model? store as user_id = null or map teacher->user_id if exists
        // Here: assume Teacher has user_id nullable, else set user_id = null
        $userId = $teacher->user_id ?? null;

        $media = $this->mediaService->upload($file, $userId ?? 0, $isPrivate);

        return $this->successResponse(new MediaFileResource($media), 'Uploaded', 201);
    }
}
