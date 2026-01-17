<?php

namespace App\Http\Controllers\Teacher\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember', false);

        if (!Auth::guard('teacher')->attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $teacher = Auth::guard('teacher')->user();

        if (!$teacher->active) {
            Auth::guard('teacher')->logout();
            throw ValidationException::withMessages([
                'email' => ['Your account is inactive.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
            ],
            'message' => 'Login successful.',
        ]);
    }
}
