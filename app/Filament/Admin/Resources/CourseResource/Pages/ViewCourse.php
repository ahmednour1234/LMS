<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Domain\Training\Enums\DeliveryType;
use App\Filament\Admin\Resources\CourseResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCourse extends ViewRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Course Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('code')
                            ->label(__('courses.code')),
                        Infolists\Components\TextEntry::make('name')
                            ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
                            ->label(__('courses.name')),
                        Infolists\Components\TextEntry::make('program.code')
                            ->label(__('courses.program')),
                        Infolists\Components\TextEntry::make('delivery_type')
                            ->formatStateUsing(function ($state) {
                                $value = $state instanceof DeliveryType ? $state->value : (string) $state;
                                return __('courses.delivery_type_options.' . $value);
                            })
                            ->badge()
                            ->label(__('courses.delivery_type')),
                        Infolists\Components\TextEntry::make('duration_hours')
                            ->label(__('courses.duration_hours'))
                            ->suffix(' ' . __('courses.hours')),
                        Infolists\Components\IconEntry::make('is_active')
                            ->boolean()
                            ->label(__('courses.is_active')),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make(__('Teacher Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('ownerTeacher.name')
                            ->label(__('Owner Teacher')),
                        Infolists\Components\RepeatableEntry::make('teachers')
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label(__('Name')),
                                Infolists\Components\TextEntry::make('email')
                                    ->label(__('Email')),
                            ])
                            ->columns(2)
                            ->label(__('Additional Teachers')),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }
}

