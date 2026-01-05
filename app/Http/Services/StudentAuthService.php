<?php

namespace App\Http\Services;

use App\Domain\Enrollment\Models\Student;
use App\Exceptions\BusinessException;
use App\Http\Enums\ApiErrorCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentAuthService
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Register a new student.
     *
     * @param array $data
     * @return Student
     */
    public function register(array $data): Student
    {
        // Hash password
        $data['password'] = Hash::make($data['password']);
        
        // Set default status to inactive
        $data['status'] = 'inactive';
        $data['email_verified_at'] = null;

        // Create student
        $student = Student::create($data);

        // Generate and send OTP
        $otp = $this->otpService->generate($student->email, 'register_verify', $student->id);
        $this->sendOtpEmail($student->email, $otp->code, 'register_verify');

        return $student;
    }

    /**
     * Login a student.
     *
     * @param string $email
     * @param string $password
     * @return array ['token' => string, 'student' => Student]
     * @throws BusinessException
     */
    public function login(string $email, string $password): array
    {
        $student = Student::where('email', $email)->first();

        if (!$student || !Hash::check($password, $student->password)) {
            throw new BusinessException(
                ApiErrorCode::UNAUTHORIZED,
                'Invalid email or password.',
                null,
                401
            );
        }

        // Check if student is active
        if ($student->status !== 'active') {
            throw new BusinessException(
                ApiErrorCode::EMAIL_NOT_VERIFIED,
                'Email address has not been verified. Please verify your email to continue.',
                null,
                403
            );
        }

        // Generate JWT token
        $token = JWTAuth::fromUser($student);

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'student' => $student,
        ];
    }

    /**
     * Verify OTP and activate student, then auto-login.
     *
     * @param string $email
     * @param string $code
     * @return array ['token' => string, 'student' => Student]
     * @throws BusinessException
     */
    public function verifyOtp(string $email, string $code): array
    {
        // Validate OTP
        if (!$this->otpService->validate($email, $code, 'register_verify')) {
            throw new BusinessException(
                ApiErrorCode::VALIDATION_ERROR,
                'Invalid or expired OTP code.',
                null,
                422
            );
        }

        // Get the OTP record to consume it
        $otp = $this->otpService->getOtp($email, $code, 'register_verify');
        if (!$otp) {
            throw new BusinessException(
                ApiErrorCode::VALIDATION_ERROR,
                'Invalid or expired OTP code.',
                null,
                422
            );
        }

        // Get student
        $student = Student::where('email', $email)->firstOrFail();

        // Consume OTP
        $this->otpService->consume($otp);

        // Activate student
        $student->update([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        // Generate JWT token (auto-login)
        $token = JWTAuth::fromUser($student);

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'student' => $student,
        ];
    }

    /**
     * Send forgot password OTP.
     *
     * @param string $email
     * @return void
     * @throws BusinessException
     */
    public function forgotPassword(string $email): void
    {
        $student = Student::where('email', $email)->first();

        if (!$student) {
            // Don't reveal if email exists for security
            return;
        }

        // Generate OTP
        $otp = $this->otpService->generate($email, 'password_reset', $student->id);
        $this->sendOtpEmail($email, $otp->code, 'password_reset');
    }

    /**
     * Reset password using OTP.
     *
     * @param string $email
     * @param string $code
     * @param string $password
     * @return void
     * @throws BusinessException
     */
    public function resetPassword(string $email, string $code, string $password): void
    {
        // Validate OTP
        if (!$this->otpService->validate($email, $code, 'password_reset')) {
            throw new BusinessException(
                ApiErrorCode::VALIDATION_ERROR,
                'Invalid or expired OTP code.',
                null,
                422
            );
        }

        // Get the OTP record to consume it
        $otp = $this->otpService->getOtp($email, $code, 'password_reset');
        if (!$otp) {
            throw new BusinessException(
                ApiErrorCode::VALIDATION_ERROR,
                'Invalid or expired OTP code.',
                null,
                422
            );
        }

        // Get student
        $student = Student::where('email', $email)->firstOrFail();

        // Update password
        $student->update([
            'password' => Hash::make($password),
        ]);

        // Consume OTP
        $this->otpService->consume($otp);
    }

    /**
     * Logout the authenticated student (invalidate token).
     *
     * @return void
     */
    public function logout(): void
    {
        Auth::guard('students')->logout();
    }

    /**
     * Refresh the JWT token.
     *
     * @return string
     */
    public function refreshToken(): string
    {
        return Auth::guard('students')->refresh();
    }

    /**
     * Send OTP email.
     *
     * @param string $email
     * @param string $code
     * @param string $purpose
     * @return void
     */
    protected function sendOtpEmail(string $email, string $code, string $purpose): void
    {
        // In development, just log it
        if (app()->environment('dev', 'local', 'development')) {
            \Log::info('OTP Email', [
                'email' => $email,
                'code' => $code,
                'purpose' => $purpose,
            ]);
            return;
        }

        // Send email using mailable
        Mail::to($email)->send(new \App\Mail\OtpMail($code, $purpose));
    }
}

