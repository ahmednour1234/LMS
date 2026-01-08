<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Domain\Training\Models\Teacher;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\LoginTeacherRequest;
use App\Http\Requests\Teacher\RegisterTeacherRequest;
use App\Http\Requests\Teacher\UpdateTeacherProfileRequest;
use App\Http\Resources\Api\V1\Public\TeacherResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class TeacherAuthController extends ApiController
{
    public function register(RegisterTeacherRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['active'] = 1;
        
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('teachers', 'public');
        }
        
        $teacher = Teacher::create($data);

        $token = JWTAuth::fromUser($teacher);

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'teacher' => $teacher,
        ], 'Registration successful.', 201);
    }

    public function login(LoginTeacherRequest $request): JsonResponse
    {
        $teacher = Teacher::where('email', $request->email)->first();

        if (!$teacher || !Hash::check($request->password, $teacher->password)) {
            return $this->errorResponse(
                \App\Http\Enums\ApiErrorCode::UNAUTHORIZED,
                'Invalid email or password.',
                null,
                401
            );
        }

        if (!$teacher->active) {
            return $this->errorResponse(
                \App\Http\Enums\ApiErrorCode::UNAUTHORIZED,
                'Account is inactive.',
                null,
                403
            );
        }

        $token = JWTAuth::fromUser($teacher);

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'teacher' => $teacher,
        ], 'Login successful.');
    }

    public function logout(): JsonResponse
    {
        Auth::guard('teacher')->logout();

        return $this->successResponse(null, 'Logged out successfully.');
    }

    public function me(): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();

        return $this->successResponse($teacher, 'Teacher profile retrieved successfully.');
    }

    public function refresh(): JsonResponse
    {
        $token = Auth::guard('teacher')->refresh();

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Token refreshed successfully.');
    }

    public function updateProfile(UpdateTeacherProfileRequest $request): JsonResponse
    {
        $teacher = Auth::guard('teacher')->user();
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($teacher->photo && Storage::disk('public')->exists($teacher->photo)) {
                Storage::disk('public')->delete($teacher->photo);
            }
            $data['photo'] = $request->file('photo')->store('teachers', 'public');
        }

        $teacher->update($data);
        $teacher->refresh();

        return $this->successResponse(
            new TeacherResource($teacher),
            'Profile updated successfully.'
        );
    }
}

