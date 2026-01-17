<?php

namespace App\Filament\Teacher\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Password;

class ResetPassword extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = null;
    
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.teacher.pages.reset-password';

    public ?array $data = [];
    public string $token = '';
    public string $email = '';

    public function mount(?string $token = null, ?string $email = null): void
    {
        $this->token = $token ?? request()->query('token', '');
        $this->email = $email ?? request()->query('email', '');

        $this->form->fill([
            'email' => $this->email,
            'token' => $this->token,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('token')
                    ->label('Token')
                    ->required()
                    ->hidden(),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->dehydrated(true),
                TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->required()
                    ->same('password')
                    ->dehydrated(false),
            ])
            ->statePath('data');
    }

    public function resetPassword(): void
    {
        $data = $this->form->getState();

        $status = Password::broker('teachers')->reset(
            [
                'email' => $data['email'],
                'token' => $data['token'],
                'password' => $data['password'],
            ],
            function ($teacher, $password) {
                $teacher->password = $password;
                $teacher->remember_token = null;
                $teacher->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', 'Password reset successfully. You can now login.');
            $this->redirect(route('filament.teacher.auth.login'));
        } else {
            $this->addError('data.email', 'Password reset failed. The token may be invalid or expired.');
        }
    }

    public function getHeading(): string | Htmlable
    {
        return 'Reset Password';
    }

    public function getSubHeading(): string | Htmlable | null
    {
        return 'Enter your new password';
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
