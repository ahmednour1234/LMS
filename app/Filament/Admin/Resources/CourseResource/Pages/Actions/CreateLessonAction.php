<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages\Actions;

use App\Domain\Training\Enums\LessonType;
use App\Domain\Training\Models\CourseSection;
use App\Domain\Training\Models\Lesson;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;

class CreateLessonAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'createLesson';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Create Lesson'))
            ->icon('heroicon-o-plus')
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
                    ->default(function ($livewire) {
                        return $livewire->mountedActionData['sectionId'] ?? null;
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
                    ->label(__('Active'))
                    ->default(true),
            ])
            ->action(function (array $data, $livewire) {
                $section = CourseSection::findOrFail($data['section_id']);
                
                // Get max sort_order for this section
                $maxSortOrder = Lesson::where('section_id', $section->id)->max('sort_order') ?? 0;
                
                Lesson::create([
                    'section_id' => $section->id,
                    'title' => [
                        'ar' => $data['title']['ar'],
                        'en' => $data['title']['en'],
                    ],
                    'description' => [
                        'ar' => $data['description']['ar'] ?? null,
                        'en' => $data['description']['en'] ?? null,
                    ],
                    'lesson_type' => $data['lesson_type'],
                    'sort_order' => $maxSortOrder + 1,
                    'estimated_minutes' => $data['estimated_minutes'] ?? null,
                    'is_preview' => $data['is_preview'] ?? false,
                    'is_active' => $data['is_active'] ?? true,
                ]);

                Notification::make()
                    ->title(__('Lesson created successfully'))
                    ->success()
                    ->send();

                $livewire->dispatch('$refresh');
            })
            ->modalWidth('md');
    }
}

