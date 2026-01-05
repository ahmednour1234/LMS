<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages\Actions;

use App\Domain\Training\Models\CourseSection;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;

class EditSectionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'editSection';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Edit Section'))
            ->icon('heroicon-o-pencil')
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
                    ->label(__('Active')),
            ])
            ->fillForm(function ($livewire) {
                $sectionId = $livewire->mountedActionData['section'] ?? null;
                if (!$sectionId) {
                    return [];
                }
                $section = \App\Domain\Training\Models\CourseSection::find($sectionId);
                if (!$section) {
                    return [];
                }
                return [
                    'title' => $section->title,
                    'description' => $section->description,
                    'is_active' => $section->is_active,
                ];
            })
            ->action(function (array $data, $livewire) {
                $sectionId = $livewire->mountedActionData['section'] ?? null;
                if (!$sectionId) {
                    return;
                }
                $section = \App\Domain\Training\Models\CourseSection::findOrFail($sectionId);
                $section->update([
                    'title' => [
                        'ar' => $data['title']['ar'],
                        'en' => $data['title']['en'],
                    ],
                    'description' => [
                        'ar' => $data['description']['ar'] ?? null,
                        'en' => $data['description']['en'] ?? null,
                    ],
                    'is_active' => $data['is_active'] ?? true,
                ]);

                Notification::make()
                    ->title(__('Section updated successfully'))
                    ->success()
                    ->send();

                $livewire->dispatch('$refresh');
            })
            ->modalWidth('md');
    }
}

