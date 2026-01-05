<?php

namespace App\Http\Services;

use App\Models\OtpCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OtpService
{
    /**
     * OTP expiration time in minutes.
     */
    protected int $expirationMinutes = 10;

    /**
     * Maximum attempts allowed for OTP validation.
     */
    protected int $maxAttempts = 3;

    /**
     * Generate a new OTP code for the given email and purpose.
     *
     * @param string $email
     * @param string $purpose 'register_verify' or 'password_reset'
     * @param int|null $studentId
     * @return OtpCode
     */
    public function generate(string $email, string $purpose, ?int $studentId = null): OtpCode
    {
        // Invalidate any existing OTP codes for this email and purpose
        OtpCode::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        // Generate OTP code
        $code = $this->generateCode();

        // Create new OTP record
        return OtpCode::create([
            'student_id' => $studentId,
            'email' => $email,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => Carbon::now()->addMinutes($this->expirationMinutes),
            'attempts' => 0,
        ]);
    }

    /**
     * Validate an OTP code for the given email and purpose.
     *
     * @param string $email
     * @param string $code
     * @param string $purpose
     * @return bool
     */
    public function validate(string $email, string $code, string $purpose): bool
    {
        $otp = OtpCode::where('email', $email)
            ->where('purpose', $purpose)
            ->valid()
            ->notConsumed()
            ->latest()
            ->first();

        if (!$otp) {
            return false;
        }

        // Check if max attempts exceeded
        if ($otp->hasExceededMaxAttempts($this->maxAttempts)) {
            return false;
        }

        // Increment attempts
        $otp->incrementAttempts();

        // Validate code
        if ($otp->code !== $code) {
            return false;
        }

        return true;
    }

    /**
     * Get the OTP code record for validation and consumption.
     *
     * @param string $email
     * @param string $code
     * @param string $purpose
     * @return OtpCode|null
     */
    public function getOtp(string $email, string $code, string $purpose): ?OtpCode
    {
        return OtpCode::where('email', $email)
            ->where('code', $code)
            ->where('purpose', $purpose)
            ->valid()
            ->notConsumed()
            ->latest()
            ->first();
    }

    /**
     * Consume (mark as used) an OTP code.
     *
     * @param OtpCode $otp
     * @return void
     */
    public function consume(OtpCode $otp): void
    {
        $otp->consume();
    }

    /**
     * Clean up expired OTP codes.
     *
     * @return int Number of deleted records
     */
    public function cleanupExpired(): int
    {
        return OtpCode::where('expires_at', '<', now())
            ->orWhere(function ($query) {
                $query->whereNotNull('consumed_at')
                    ->where('created_at', '<', Carbon::now()->subDays(7));
            })
            ->delete();
    }

    /**
     * Generate a random 6-digit OTP code.
     * In development environment, always return 111111.
     *
     * @return string
     */
    protected function generateCode(): string
    {
        // In development, always return 111111
        if (app()->environment('dev', 'local', 'development')) {
            return '111111';
        }

        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}

