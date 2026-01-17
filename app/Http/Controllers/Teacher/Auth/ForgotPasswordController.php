<?php

namespace App\Http\Controllers\Teacher\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('teachers')->sendResetLink(
            $request->only('email')
        );

        return response()->json([
            'success' => true,
            'message' => 'If the email exists, a password reset link has been sent.',
        ]);
    }
}
