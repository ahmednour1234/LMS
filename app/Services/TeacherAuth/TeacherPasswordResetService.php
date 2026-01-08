<?php

namespace App\Services\TeacherAuth;

use App\Domain\Training\Models\Teacher;
use App\Exceptions\BusinessException;
use App\Http\Enums\ApiErrorCode;
use App\Mail\TeacherPasswordResetCodeMail;
use App\Models\TeacherPasswordReset;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TeacherPasswordResetService
{
    public function sendCode(string $email): void
    {
        $code = app()->environment(['local', 'development']) 
            ? '111111' 
            : (string) random_int(100000, 999999);

        $expiresAt = Carbon::now()->addMinutes(15);

        TeacherPasswordReset::updateOrCreate(
            ['email' => $email],
            [
                'code' => $code,
                'expires_at' => $expiresAt,
                'created_at' => Carbon::now(),
            ]
        );

        if (!app()->environment(['local', 'development'])) {
            Mail::to($email)->send(new TeacherPasswordResetCodeMail($code));
        }
    }

    public function resetPassword(string $email, string $code, string $password): void
    {
        DB::transaction(function () use ($email, $code, $password) {
            $reset = TeacherPasswordReset::where('email', $email)
                ->where('code', $code)
                ->where('expires_at', '>', Carbon::now())
                ->latest('created_at')
                ->first();

            if (!$reset) {
                throw new BusinessException(
                    ApiErrorCode::VALIDATION_ERROR,
                    'Invalid or expired reset code.',
                    null,
                    422
                );
            }

            $teacher = Teacher::where('email', $email)->firstOrFail();
            $teacher->update(['password' => $password]);

            $reset->delete();
        });
    }
}

