<?php

namespace App\Filament\Teacher\Pages\Auth;

use Filament\Pages\Auth\PasswordReset\ResetPassword as BaseResetPassword;
use Illuminate\Support\Facades\Password;
use Filament\Notifications\Notification;
use Filament\Http\Responses\Auth\Contracts\PasswordResetResponse;

class ResetPassword extends BaseResetPassword
{
    protected static string $view = 'filament.teacher.pages.reset-password';

    public function getCachedSubNavigation(): array
    {
        return [];
    }

    public function resetPassword(): ?PasswordResetResponse
    {
        $data = $this->form->getState();

        $status = Password::broker('teachers')->reset(
            [
                'email' => $data['email'],
                'token' => $data['token'],
                'password' => $data['password'],
            ],
            function ($teacher, $password) {
                $teacher->forceFill([
                    'password' => \Illuminate\Support\Facades\Hash::make($password),
                ])->save();

                $teacher->setRememberToken(\Illuminate\Support\Str::random(60));
                $teacher->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/password-reset/reset-password.notifications.password_reset.title'))
                ->success()
                ->send();

            return app(PasswordResetResponse::class);
        } else {
            Notification::make()
                ->title(__($status))
                ->danger()
                ->send();

            return null;
        }
    }
}
