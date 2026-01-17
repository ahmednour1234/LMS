<?php

namespace App\Filament\Teacher\Pages\Auth;

use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Illuminate\Support\Facades\Password;
use Filament\Notifications\Notification;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    protected static string $view = 'filament.teacher.pages.forgot-password';

    public function getCachedSubNavigation(): array
    {
        return [];
    }

    public function getSubNavigationPosition(): ?string
    {
        return null;
    }

    public function getWidgetData(): array
    {
        return [];
    }

    public function sendPasswordResetLink(): void
    {
        $data = $this->form->getState();

        $status = Password::broker('teachers')->sendResetLink(
            ['email' => $data['email']]
        );

        if ($status === Password::RESET_LINK_SENT) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled.title', [
                    'seconds' => config('auth.passwords.teachers.throttle', 60),
                    'minutes' => ceil(config('auth.passwords.teachers.throttle', 60) / 60),
                ]))
                ->success()
                ->send();

            $this->form->fill();
        } else {
            Notification::make()
                ->title(__($status))
                ->danger()
                ->send();
        }
    }
}
