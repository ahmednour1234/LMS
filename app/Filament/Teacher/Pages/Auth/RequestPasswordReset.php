<?php

namespace App\Filament\Teacher\Pages\Auth;

use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    /**
     * IMPORTANT:
     * Do NOT override $view unless you fully know Filament's expected layout sections.
     * The custom view was causing Livewire to call getHeader() which doesn't exist.
     */

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

        // Use teachers broker (isolated from other guards)
        $status = Password::broker('teachers')->sendResetLink([
            'email' => $data['email'],
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/password-reset/request-password-reset.notifications.sent.title'))
                ->success()
                ->send();

            // Optional: reset form
            $this->form->fill();
            return;
        }

        if ($status === Password::RESET_THROTTLED) {
            $seconds = (int) config('auth.passwords.teachers.throttle', 60);

            Notification::make()
                ->title(__('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled.title', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]))
                ->warning()
                ->send();

            return;
        }

        // Example: invalid user or unknown error
        Notification::make()
            ->title(__($status))
            ->danger()
            ->send();
    }
}
