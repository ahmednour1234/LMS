<?php

namespace App\Filament\Teacher\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Password;

class ForgotPassword extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = null;
    
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.teacher.pages.forgot-password';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
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
            ])
            ->statePath('data');
    }

    public function sendResetLink(): void
    {
        $data = $this->form->getState();

        Password::broker('teachers')->sendResetLink(['email' => $data['email']]);

        session()->flash('status', 'If the email exists, a password reset link has been sent.');
        $this->redirect(route('filament.teacher.auth.login'));
    }

    public function getHeading(): string | Htmlable
    {
        return 'Forgot Password';
    }

    public function getSubHeading(): string | Htmlable | null
    {
        return 'Enter your email address and we will send you a password reset link.';
    }

    public static function canAccess(): bool
    {
        return !auth('teacher')->check();
    }
}
