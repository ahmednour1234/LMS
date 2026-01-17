<?php

namespace App\Filament\Teacher\Pages;

use App\Domain\Training\Models\Teacher;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class Register extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.teacher.pages.register';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Phone')
                    ->tel()
                    ->maxLength(255),
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

    public function register(): void
    {
        $data = $this->form->getState();

        $teacher = Teacher::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'active' => true,
        ]);

        Auth::guard('teacher')->login($teacher);

        $this->redirect(route('filament.teacher.pages.dashboard'));
    }

    public function getHeading(): string | Htmlable
    {
        return 'Teacher Registration';
    }

    public function getSubHeading(): string | Htmlable | null
    {
        return 'Create your teacher account';
    }


}
