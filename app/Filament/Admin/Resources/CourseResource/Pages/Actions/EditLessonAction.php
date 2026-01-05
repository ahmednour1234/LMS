<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages\Actions;

use App\Domain\Training\Enums\LessonType;
use App\Domain\Training\Models\CourseSection;
use App\Domain\Training\Models\Lesson;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;

class EditLessonAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'editLesson';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Edit Lesson'))
            ->icon('heroicon-o-pencil')
            ->form([
                Forms\Components\Select::make('section_id')
                    ->label(__('Section'))
                    ->options(function ($livewire) {
                        $course = $livewire->getRecord();
                        return CourseSection::where('course_id', $course->id)
                            ->get()
                            ->mapWithKeys(function ($section) {
                                $title = 'Untitled';
                                if (is_array($section->title)) {
                                    $locale = app()->getLocale();
                                    $title = $section->title[$locale] 
                                        ?? $section->title['en'] 
                                        ?? $section->title['ar'] 
                                        ?? null;
                                    
                                    // Ensure title is a non-empty string
                                    if (empty($title) || !is_string($title)) {
                                        $title = 'Untitled';
                                    }
                                } elseif ($section->title !== null && is_string($section->title)) {
                                    $title = $section->title;
                                }
                                
                                return [
                                    $section->id => (string) $title
                                ];
                            });
                    })
                    ->required()
                    ->searchable(),
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
                Forms\Components\Select::make('lesson_type')
                    ->label(__('Lesson Type'))
                    ->options([
                        LessonType::RECORDED->value => __('Recorded'),
                        LessonType::LIVE->value => __('Live'),
                        LessonType::MIXED->value => __('Mixed'),
                    ])
                    ->required(),
                Forms\Components\TextInput::make('estimated_minutes')
                    ->label(__('Estimated Minutes'))
                    ->numeric()
                    ->minValue(1),
                Forms\Components\Toggle::make('is_preview')
                    ->label(__('Preview Lesson')),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('Active')),
                Forms\Components\DateTimePicker::make('published_at')
                    ->label(__('Published At'))
                    ->native(false),
            ])
            ->fillForm(function ($livewire) {
                $lessonId = $livewire->mountedActionData['lesson'] ?? null;
                if (!$lessonId) {
                    return [];
                }
                $lesson = Lesson::find($lessonId);
                if (!$lesson) {
                    return [];
                }
                return [
                    'section_id' => $lesson->section_id,
                    'title' => $lesson->title,
                    'description' => $lesson->description,
                    'lesson_type' => $lesson->lesson_type?->value,
                    'estimated_minutes' => $lesson->estimated_minutes,
                    'is_preview' => $lesson->is_preview,
                    'is_active' => $lesson->is_active,
                    'published_at' => $lesson->published_at,
                ];
            })
            ->action(function (array $data, $livewire) {
                $lessonId = $livewire->mountedActionData['lesson'] ?? null;
                if (!$lessonId) {
                    return;
                }
                $lesson = Lesson::findOrFail($lessonId);
                $lesson->update([
                    'section_id' => $data['section_id'],
                    'title' => [
                        'ar' => $data['title']['ar'],
                        'en' => $data['title']['en'],
                    ],
                    'description' => [
                        'ar' => $data['description']['ar'] ?? null,
                        'en' => $data['description']['en'] ?? null,
                    ],
                    'lesson_type' => $data['lesson_type'],
                    'estimated_minutes' => $data['estimated_minutes'] ?? null,
                    'is_preview' => $data['is_preview'] ?? false,
                    'is_active' => $data['is_active'] ?? true,
                    'published_at' => $data['published_at'] ?? null,
                ]);

                Notification::make()
                    ->title(__('Lesson updated successfully'))
                    ->success()
                    ->send();

                $livewire->dispatch('$refresh');
            })
            ->modalWidth('md');
    }
}

