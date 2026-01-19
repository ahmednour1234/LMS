<?php

namespace App\Filament\Admin\Resources\ExamResource\Pages;

use App\Domain\Training\Models\Exam;
use App\Filament\Admin\Resources\ExamResource;
use App\Support\Helpers\MultilingualHelper;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\EditRecord;

class EditExam extends EditRecord
{
    protected static string $resource = ExamResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\Action::make('preview')
                ->label(__('exams.preview_exam'))
                ->icon('heroicon-o-eye')
                ->url(fn () => static::getResource()::getUrl('edit', ['record' => $this->record]) . '?activeTab=preview')
                ->openUrlInNewTab(false),
            Actions\DeleteAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('exam_tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('details')
                            ->label(__('exams.details'))
                            ->schema([
                                Forms\Components\Select::make('course_id')
                                    ->relationship('course', 'code')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label(__('exams.course'))
                                    ->reactive(),
                                Forms\Components\Select::make('lesson_id')
                                    ->relationship('lesson', 'id', function ($query, $get) {
                                        $courseId = $get('course_id');
                                        if ($courseId) {
                                            $query->whereHas('section', fn ($q) => $q->where('course_id', $courseId));
                                        }
                                        return $query->orderBy('id');
                                    })
                                    ->getOptionLabelUsing(function ($record): string {
                                        if (is_object($record)) {
                                            return MultilingualHelper::formatMultilingualField($record->title) ?: 'N/A';
                                        }
                                        $lesson = \App\Domain\Training\Models\Lesson::find($record);
                                        return $lesson ? (MultilingualHelper::formatMultilingualField($lesson->title) ?: 'N/A') : 'N/A';
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label(__('exams.lesson'))
                                    ->visible(fn ($get) => $get('course_id')),
                                Forms\Components\TextInput::make('title.ar')
                                    ->label(__('exams.title_ar'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('title.en')
                                    ->label(__('exams.title_en'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description.ar')
                                    ->label(__('exams.description_ar'))
                                    ->rows(3),
                                Forms\Components\Textarea::make('description.en')
                                    ->label(__('exams.description_en'))
                                    ->rows(3),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'mcq' => __('exams.type_options.mcq'),
                                        'essay' => __('exams.type_options.essay'),
                                        'mixed' => __('exams.type_options.mixed'),
                                    ])
                                    ->required()
                                    ->label(__('exams.type')),
                                Forms\Components\TextInput::make('total_score')
                                    ->numeric()
                                    ->default(0)
                                    ->label(__('exams.total_grade'))
                                    ->dehydrated(false)
                                    ->disabled()
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                        if ($record) {
                                            $totalPoints = $record->questions()->sum('points');
                                            $component->state($totalPoints);
                                        }
                                    }),
                                Forms\Components\TextInput::make('duration_minutes')
                                    ->numeric()
                                    ->label(__('exams.duration_minutes')),
                                Forms\Components\Toggle::make('is_active')
                                    ->label(__('exams.is_active'))
                                    ->default(true),
                            ]),
                        Forms\Components\Tabs\Tab::make('preview')
                            ->label(__('exams.preview'))
                            ->schema([
                                Forms\Components\Section::make(__('exams.exam_info'))
                                    ->schema([
                                        Forms\Components\Placeholder::make('course')
                                            ->label(__('exams.course'))
                                            ->content(fn ($record) => $record?->course?->code ?? '-'),
                                        Forms\Components\Placeholder::make('title')
                                            ->label(__('exams.title'))
                                            ->content(fn ($record) => $record ? MultilingualHelper::formatMultilingualField($record->title) : '-'),
                                        Forms\Components\Placeholder::make('type')
                                            ->label(__('exams.type'))
                                            ->content(fn ($record) => $record ? __('exams.type_options.' . $record->type) : '-'),
                                        Forms\Components\Placeholder::make('duration')
                                            ->label(__('exams.duration_minutes'))
                                            ->content(fn ($record) => $record?->duration_minutes ? $record->duration_minutes . ' ' . __('exams.minutes') : '-'),
                                        Forms\Components\Placeholder::make('status')
                                            ->label(__('exams.is_active'))
                                            ->content(fn ($record) => $record?->is_active ? __('exams.active') : __('exams.inactive')),
                                    ])
                                    ->columns(2),
                                Forms\Components\Section::make(__('exams.summary'))
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_questions')
                                            ->label(__('exams.total_questions'))
                                            ->content(fn ($record) => $record ? $record->questions()->count() : 0),
                                        Forms\Components\Placeholder::make('total_points')
                                            ->label(__('exams.total_points'))
                                            ->content(fn ($record) => $record ? number_format($record->questions()->sum('points'), 2) : '0.00'),
                                        Forms\Components\Placeholder::make('total_score')
                                            ->label(__('exams.total_grade'))
                                            ->content(fn ($record) => $record ? number_format($record->total_score, 2) : '0.00'),
                                    ])
                                    ->columns(3),
                                Forms\Components\Section::make(__('exams.questions'))
                                    ->schema([
                                        Forms\Components\Repeater::make('preview_questions')
                                            ->schema([
                                                Forms\Components\Placeholder::make('question_number')
                                                    ->label(__('exams.question_number'))
                                                    ->content(fn ($state, $get) => 'Q' . ($get('../../index') + 1)),
                                                Forms\Components\Placeholder::make('type')
                                                    ->label(__('exam_questions.type'))
                                                    ->content(fn ($state) => __('exam_questions.type_options.' . ($state ?? 'mcq'))),
                                                Forms\Components\Placeholder::make('question')
                                                    ->label(__('exam_questions.question'))
                                                    ->content(fn ($state) => is_array($state) ? ($state[app()->getLocale()] ?? $state['ar'] ?? '') : ($state ?? '')),
                                                Forms\Components\Placeholder::make('points')
                                                    ->label(__('exam_questions.points'))
                                                    ->content(fn ($state) => number_format($state ?? 0, 2)),
                                                Forms\Components\Placeholder::make('options')
                                                    ->label(__('exam_questions.options'))
                                                    ->visible(fn ($get) => ($get('../../type') ?? '') === 'mcq')
                                                    ->content(function ($state, $get) {
                                                        if (($get('../../type') ?? '') !== 'mcq' || !$state) {
                                                            return '-';
                                                        }
                                                        $options = is_array($state) ? $state : [];
                                                        $correctIndex = $get('../../correct_answer') ?? null;
                                                        $locale = app()->getLocale();
                                                        $html = '<ul class="list-disc list-inside space-y-1">';
                                                        foreach ($options as $index => $option) {
                                                            if (is_array($option)) {
                                                                $text = $option[$locale] ?? $option['en'] ?? $option['ar'] ?? '';
                                                            } elseif (is_string($option)) {
                                                                $text = $option;
                                                            } else {
                                                                $text = '';
                                                            }
                                                            $isCorrect = $correctIndex !== null && (int)$index === (int)$correctIndex;
                                                            $html .= '<li' . ($isCorrect ? ' class="font-bold text-success-600"' : '') . '>';
                                                            $html .= htmlspecialchars($text);
                                                            if ($isCorrect) {
                                                                $html .= ' ✓';
                                                            }
                                                            $html .= '</li>';
                                                        }
                                                        $html .= '</ul>';
                                                        return new \Illuminate\Support\HtmlString($html);
                                                    }),
                                                Forms\Components\Placeholder::make('correct_answer_tf')
                                                    ->label(__('exam_questions.correct_answer'))
                                                    ->visible(fn ($get) => ($get('../../type') ?? '') === 'true_false')
                                                    ->content(function ($state, $get) {
                                                        $correct = $get('../../correct_answer');
                                                        if ($correct === '1' || $correct === 1 || (int)$correct === 1) {
                                                            return __('exam_questions.true_false_true');
                                                        }
                                                        return __('exam_questions.true_false_false');
                                                    }),
                                                Forms\Components\Placeholder::make('essay_note')
                                                    ->label('')
                                                    ->visible(fn ($get) => ($get('../../type') ?? '') === 'essay')
                                                    ->content(__('exams.essay_question')),
                                            ])
                                            ->defaultItems(0)
                                            ->dehydrated(false)
                                            ->disabled()
                                            ->itemLabel(fn ($state) => 'Q' . ($state['order'] ?? ''))
                                            ->afterStateHydrated(function (Forms\Components\Repeater $component, $state, $record) {
                                                if ($record) {
                                                    $questions = $record->questions()->orderBy('order')->get()->map(function ($q) {
                                                        return [
                                                            'type' => $q->type,
                                                            'question' => $q->question,
                                                            'points' => $q->points,
                                                            'options' => $q->options,
                                                            'correct_answer' => $q->correct_answer,
                                                            'order' => $q->order,
                                                        ];
                                                    })->toArray();
                                                    $component->state($questions);
                                                }
                                            }),
                                    ]),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('publish')
                                        ->label(__('exams.publish'))
                                        ->icon('heroicon-o-check-circle')
                                        ->color('success')
                                        ->requiresConfirmation()
                                        ->action(function ($record) {
                                            $questionsCount = $record->questions()->count();
                                            $totalPoints = $record->questions()->sum('points');

                                            if ($questionsCount < 1) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title(__('exams.publish_error'))
                                                    ->body(__('exams.publish_error_no_questions'))
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            if ($totalPoints <= 0) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title(__('exams.publish_error'))
                                                    ->body(__('exams.publish_error_no_points'))
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $record->update(['is_active' => true]);
                                            \Filament\Notifications\Notification::make()
                                                ->title(__('exams.published'))
                                                ->success()
                                                ->send();
                                        }),
                                ]),
                            ]),
                    ])
                    ->persistTabInQueryString('activeTab'),
            ]);
    }
}
