<?php

namespace App\Filament\Teacher\Pages\Auth;

use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected static string $view = 'filament.teacher.pages.auth.login';

    public function canAccess(): bool
    {
        return !\Filament\Facades\Filament::auth()->check();
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (\DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (!\Filament\Facades\Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $teacher = \Filament\Facades\Filament::auth()->user();

        if (!$teacher || !$teacher->active) {
            \Filament\Facades\Filament::auth()->logout();
            throw ValidationException::withMessages([
                'data.email' => 'Your account is inactive.',
            ]);
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }
}
