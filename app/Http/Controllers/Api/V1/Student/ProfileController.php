<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\V1\Student\UpdateProfileRequest;
use App\Http\Resources\Api\V1\StudentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * @group Student Profile
 * 
 * Profile management endpoints for authenticated students.
 */
class ProfileController extends ApiController
{
    /**
     * Update Profile
     * 
     * Update the authenticated student's profile information. Supports updating name, phone, sex, and profile image.
     * 
     * @authenticated
     * 
     * @bodyParam name string optional The student's full name. Example: John Doe
     * @bodyParam phone string optional The student's phone number. Example: +1234567890
     * @bodyParam sex string optional The student's gender. Must be one of: male, female. Example: male
     * @bodyParam image file optional Profile image (max 5MB). Must be a valid image file.
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Profile updated successfully.",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "phone": "+1234567890",
     *     "sex": "male",
     *     "branch_id": 1,
     *     "image": "http://example.com/storage/students/image.jpg",
     *     "status": "active",
     *     "email_verified_at": "2026-01-15T12:00:00+00:00",
     *     "created_at": "2026-01-15T12:00:00+00:00"
     *   }
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "Authentication required.",
     *   "error": {
     *     "code": "UNAUTHORIZED"
     *   }
     * }
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $student = auth('students')->user();
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($student->image && Storage::disk('public')->exists($student->image)) {
                Storage::disk('public')->delete($student->image);
            }

            // Store new image
            $path = $request->file('image')->store('students', 'public');
            $data['image'] = $path;
        }

        // Update student
        $student->update($data);
        $student->refresh();

        return $this->successResponse(
            new StudentResource($student),
            'Profile updated successfully.'
        );
    }
}

