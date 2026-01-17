<?php

namespace App\Http\Controllers\Teacher\Auth;

use App\Domain\Training\Models\Teacher;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['active'] = true;

        $teacher = Teacher::create($data);

        Auth::guard('teacher')->login($teacher);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
            ],
            'message' => 'Registration successful.',
        ], 201);
    }
}
