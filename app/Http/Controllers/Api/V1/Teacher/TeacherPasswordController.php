<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Teacher\ForgotTeacherPasswordRequest;
use App\Http\Requests\Teacher\ResetTeacherPasswordRequest;
use App\Services\TeacherAuth\TeacherPasswordResetService;
use Illuminate\Http\JsonResponse;

class TeacherPasswordController extends ApiController
{
    protected TeacherPasswordResetService $resetService;

    public function __construct(TeacherPasswordResetService $resetService)
    {
        $this->resetService = $resetService;
    }

    public function forgotPassword(ForgotTeacherPasswordRequest $request): JsonResponse
    {
        $this->resetService->sendCode($request->email);

        return $this->successResponse(null, 'If the email exists, a reset code has been sent.');
    }

    public function resetPassword(ResetTeacherPasswordRequest $request): JsonResponse
    {
        $this->resetService->resetPassword(
            $request->email,
            $request->code,
            $request->password
        );

        return $this->successResponse(null, 'Password reset successfully.');
    }
}

