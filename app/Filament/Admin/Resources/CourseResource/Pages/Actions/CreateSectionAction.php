<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages\Actions;

use App\Domain\Training\Models\CourseSection;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class CreateSectionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'createSection';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Create Section'))
            ->icon('heroicon-o-plus')
            ->form([
                Forms\Components\TextInput::make('title.ar')
                    ->label(__('Title (Arabic)'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('title.en')
                    ->label(__('Title (English)'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description.ar')
                    ->label(__('Description (Arabic)'))
                    ->rows(3),
                Forms\Components\Textarea::make('description.en')
                    ->label(__('Description (English)'))
                    ->rows(3),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
            ])
            ->action(function (array $data, $livewire) {
                $course = $livewire->getRecord();
                
                // Get max order for this course
                $maxOrder = CourseSection::where('course_id', $course->id)->max('order') ?? 0;
                
                CourseSection::create([
                    'course_id' => $course->id,
                    'title' => [
                        'ar' => $data['title']['ar'],
                        'en' => $data['title']['en'],
                    ],
                    'description' => [
                        'ar' => $data['description']['ar'] ?? null,
                        'en' => $data['description']['en'] ?? null,
                    ],
                    'order' => $maxOrder + 1,
                    'is_active' => $data['is_active'] ?? true,
                ]);

                Notification::make()
                    ->title(__('Section created successfully'))
                    ->success()
                    ->send();

                $livewire->dispatch('$refresh');
            })
            ->modalWidth('md');
    }
}

