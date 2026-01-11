<?php

namespace App\Filament\Admin\Resources\ExamResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Questions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options([
                        'mcq' => __('exam_questions.type_options.mcq'),
                        'essay' => __('exam_questions.type_options.essay'),
                        'true_false' => __('exam_questions.type_options.true_false'),
                    ])
                    ->required()
                    ->label(__('exam_questions.type'))
                    ->reactive()
                    ->live(),
                Forms\Components\Textarea::make('question.ar')
                    ->label(__('exam_questions.question_ar'))
                    ->required()
                    ->rows(3),
                Forms\Components\Textarea::make('question.en')
                    ->label(__('exam_questions.question_en'))
                    ->required()
                    ->rows(3),
                Forms\Components\Repeater::make('options')
                    ->schema([
                        Forms\Components\TextInput::make('option')
                            ->required()
                            ->maxLength(255)
                            ->label(__('exam_questions.option')),
                    ])
                    ->label(__('exam_questions.options'))
                    ->visible(fn ($get) => $get('type') === 'mcq')
                    ->defaultItems(2)
                    ->minItems(2)
                    ->reactive(),
                Forms\Components\Select::make('correct_answer')
                    ->options(function ($get, $record) {
                        if ($get('type') === 'true_false') {
                            return [
                                0 => __('exam_questions.true_false_false'),
                                1 => __('exam_questions.true_false_true'),
                            ];
                        }
                        
                        $options = $get('options') ?? [];
                        if ($record && $record->options) {
                            $options = $record->options;
                        }
                        $opts = [];
                        foreach ($options as $index => $option) {
                            $text = is_array($option) ? ($option['option'] ?? '') : $option;
                            if ($text) {
                                $opts[$index] = $text;
                            }
                        }
                        return $opts;
                    })
                    ->label(__('exam_questions.correct_answer'))
                    ->visible(fn ($get) => in_array($get('type'), ['mcq', 'true_false']))
                    ->required(fn ($get) => in_array($get('type'), ['mcq', 'true_false']))
                    ->reactive(),
                Forms\Components\TextInput::make('points')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->label(__('exam_questions.points'))
                    ->minValue(0)
                    ->step(0.01),
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->default(0)
                    ->label(__('exam_questions.sort_order'))
                    ->required()
                    ->helperText(__('exam_questions.order_helper')),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('exam_questions.is_active'))
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable()
                    ->label(__('exam_questions.sort_order')),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('exam_questions.type_options.' . $state))
                    ->label(__('exam_questions.type')),
                Tables\Columns\TextColumn::make('question')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state[app()->getLocale()] ?? $state['ar'] ?? '') : ($state ?? ''))
                    ->searchable()
                    ->label(__('exam_questions.question'))
                    ->limit(50),
                Tables\Columns\TextColumn::make('points')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->label(__('exam_questions.points')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('exam_questions.is_active')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'mcq' => __('exam_questions.type_options.mcq'),
                        'essay' => __('exam_questions.type_options.essay'),
                        'true_false' => __('exam_questions.type_options.true_false'),
                    ])
                    ->label(__('exam_questions.type')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('exam_questions.is_active')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data, $livewire): array {
                        $lastOrder = $livewire->getOwnerRecord()->questions()->max('order') ?? 0;
                        $data['order'] = $lastOrder + 1;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('order', 'asc')
            ->reorderable('order');
    }
}
