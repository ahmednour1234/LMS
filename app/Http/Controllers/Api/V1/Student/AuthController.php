<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\V1\Student\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Student\LoginRequest;
use App\Http\Requests\Api\V1\Student\RegisterStudentRequest;
use App\Http\Requests\Api\V1\Student\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Student\VerifyOtpRequest;
use App\Http\Resources\Api\V1\StudentResource;
use App\Http\Services\StudentAuthService;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @group Student Auth
 * 
 * Authentication endpoints for students. All authentication uses the students table, not the default users table.
 */
class AuthController extends ApiController
{
    protected StudentAuthService $authService;

    public function __construct(StudentAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register Student
     * 
     * Register a new student account. After registration, an OTP code will be sent to the provided email address.
     * The student status will be set to 'inactive' until email verification is completed.
     * 
     * In development environment, the OTP code is always `111111`.
     * 
     * @bodyParam name string required The student's full name. Example: John Doe
     * @bodyParam email string required The student's email address (must be unique). Example: john@example.com
     * @bodyParam password string required The password (minimum 8 characters). Example: password123
     * @bodyParam password_confirmation string required Password confirmation. Example: password123
     * @bodyParam phone string optional The student's phone number. Example: +1234567890
     * @bodyParam sex string optional The student's gender. Must be one of: male, female. Example: male
     * @bodyParam branch_id integer optional The ID of the branch. Example: 1
     * @bodyParam student_code string optional Unique student code. Example: STU001
     * @bodyParam national_id string optional National ID number. Example: 123456789
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "Registration successful. Please check your email for the OTP code.",
     *   "data": null
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "The provided data is invalid.",
     *   "error": {
     *     "code": "VALIDATION_ERROR",
     *     "details": {
     *       "email": ["The email has already been taken."]
     *     }
     *   }
     * }
     */
    public function register(RegisterStudentRequest $request): JsonResponse
    {
        $student = $this->authService->register($request->validated());

        return $this->createdResponse(
            null,
            'Registration successful. Please check your email for the OTP code.'
        );
    }

    /**
     * Verify OTP
     * 
     * Verify the OTP code sent to the student's email. Upon successful verification:
     * - The student's email_verified_at will be set
     * - The student's status will be changed to 'active'
     * - A JWT token will be issued (auto-login)
     * 
     * @bodyParam email string required The student's email address. Example: john@example.com
     * @bodyParam code string required The 6-digit OTP code. Example: 111111
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Email verified successfully. You are now logged in.",
     *   "data": {
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *     "token_type": "Bearer",
     *     "student": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "phone": "+1234567890",
     *       "sex": "male",
     *       "branch_id": 1,
     *       "image": null,
     *       "status": "active",
     *       "email_verified_at": "2026-01-15T12:00:00+00:00",
     *       "created_at": "2026-01-15T12:00:00+00:00"
     *     }
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Invalid or expired OTP code.",
     *   "error": {
     *     "code": "VALIDATION_ERROR"
     *   }
     * }
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->authService->verifyOtp(
            $request->input('email'),
            $request->input('code')
        );

        return $this->successResponse([
            'token' => $result['token'],
            'token_type' => $result['token_type'],
            'student' => new StudentResource($result['student']),
        ], 'Email verified successfully. You are now logged in.');
    }

    /**
     * Login
     * 
     * Authenticate a student and return a JWT token. The student must have status 'active' (email verified).
     * 
     * @bodyParam email string required The student's email address. Example: john@example.com
     * @bodyParam password string required The student's password. Example: password123
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Login successful.",
     *   "data": {
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *     "token_type": "Bearer",
     *     "student": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "phone": "+1234567890",
     *       "sex": "male",
     *       "branch_id": 1,
     *       "image": null,
     *       "status": "active",
     *       "email_verified_at": "2026-01-15T12:00:00+00:00",
     *       "created_at": "2026-01-15T12:00:00+00:00"
     *     }
     *   }
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid email or password.",
     *   "error": {
     *     "code": "UNAUTHORIZED"
     *   }
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "Email address has not been verified. Please verify your email to continue.",
     *   "error": {
     *     "code": "EMAIL_NOT_VERIFIED"
     *   }
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password')
        );

        return $this->successResponse([
            'token' => $result['token'],
            'token_type' => $result['token_type'],
            'student' => new StudentResource($result['student']),
        ], 'Login successful.');
    }

    /**
     * Get Authenticated Student
     * 
     * Get the currently authenticated student's profile information.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Student profile retrieved successfully.",
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
    public function me(): JsonResponse
    {
        $student = auth('students')->user();

        return $this->successResponse(
            new StudentResource($student),
            'Student profile retrieved successfully.'
        );
    }

    /**
     * Logout
     * 
     * Invalidate the current JWT token, effectively logging out the student.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Logged out successfully.",
     *   "data": null
     * }
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return $this->successResponse(
            null,
            'Logged out successfully.'
        );
    }

    /**
     * Refresh Token
     * 
     * Get a new JWT token using the current valid token. This extends the session without requiring re-authentication.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Token refreshed successfully.",
     *   "data": {
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *     "token_type": "Bearer"
     *   }
     * }
     */
    public function refresh(): JsonResponse
    {
        $token = $this->authService->refreshToken();

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Token refreshed successfully.');
    }

    /**
     * Forgot Password
     * 
     * Request a password reset OTP code. An OTP will be sent to the provided email address if it exists in the system.
     * For security reasons, the response is the same whether the email exists or not.
     * 
     * @bodyParam email string required The student's email address. Example: john@example.com
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "If the email exists, an OTP code has been sent to your email address.",
     *   "data": null
     * }
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword($request->input('email'));

        return $this->successResponse(
            null,
            'If the email exists, an OTP code has been sent to your email address.'
        );
    }

    /**
     * Reset Password
     * 
     * Reset the student's password using the OTP code received via email.
     * 
     * @bodyParam email string required The student's email address. Example: john@example.com
     * @bodyParam code string required The 6-digit OTP code. Example: 111111
     * @bodyParam password string required The new password (minimum 8 characters). Example: newpassword123
     * @bodyParam password_confirmation string required Password confirmation. Example: newpassword123
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Password reset successfully. You can now login with your new password.",
     *   "data": null
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Invalid or expired OTP code.",
     *   "error": {
     *     "code": "VALIDATION_ERROR"
     *   }
     * }
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword(
            $request->input('email'),
            $request->input('code'),
            $request->input('password')
        );

        return $this->successResponse(
            null,
            'Password reset successfully. You can now login with your new password.'
        );
    }
}

