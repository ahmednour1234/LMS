<?php

namespace App\Filament\Teacher\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Auth;

class Login extends BaseLogin
{
    protected static string $view = 'filament.teacher.pages.auth.login';

    public function authenticate(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        $data = $this->form->getState();

        if (!Auth::guard('teacher')->attempt([
            'email' => $data['email'],
            'password' => $data['password'],
        ], $data['remember'] ?? false)) {
            $this->addError('data.email', __('filament-panels::pages/auth/login.messages.failed'));
            return null;
        }

        $teacher = Auth::guard('teacher')->user();

        if (!$teacher || !$teacher->active) {
            Auth::guard('teacher')->logout();
            $this->addError('data.email', 'Your account is inactive.');
            return null;
        }

        return $teacher;
    }
}
