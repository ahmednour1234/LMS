<?php

namespace App\Filament\Teacher\Pages\Auth;

use App\Domain\Training\Models\Teacher;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Auth;

class Register extends BaseRegister
{
    protected static string $view = 'filament.teacher.pages.register';

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('filament-panels::pages/auth/register.form.name.label'))
                    ->required()
                    ->maxLength(255)
                    ->autofocus()
                    ->extraInputAttributes(['tabindex' => 1]),
                TextInput::make('email')
                    ->label(__('filament-panels::pages/auth/register.form.email.label'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique('teachers', 'email')
                    ->extraInputAttributes(['tabindex' => 2]),
                TextInput::make('password')
                    ->label(__('filament-panels::pages/auth/register.form.password.label'))
                    ->password()
                    ->required()
                    ->rule(\Illuminate\Validation\Rules\Password::default())
                    ->dehydrated()
                    ->same('passwordConfirmation')
                    ->extraInputAttributes(['tabindex' => 3]),
                TextInput::make('passwordConfirmation')
                    ->label(__('filament-panels::pages/auth/register.form.password_confirmation.label'))
                    ->password()
                    ->required()
                    ->dehydrated(false)
                    ->extraInputAttributes(['tabindex' => 4]),
            ])
            ->statePath('data');
    }

    protected function handleRegistration(array $data): \Illuminate\Contracts\Auth\Authenticatable
    {
        $teacher = Teacher::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'active' => true,
        ]);

        Auth::guard('teacher')->login($teacher);

        return $teacher;
    }
}
