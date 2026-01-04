<?php

namespace App\Filament\Admin\Resources\Domain\Training\Models\TeacherResource\Pages;

use App\Filament\Admin\Resources\Domain\Training\Models\TeacherResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Table;

class ViewTeacher extends ViewRecord
{
    protected static string $resource = TeacherResource::class;

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
                Infolists\Components\Section::make(__('Teacher Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label(__('Name')),
                        Infolists\Components\TextEntry::make('email')
                            ->label(__('Email')),
                        Infolists\Components\TextEntry::make('sex')
                            ->badge()
                            ->label(__('Sex')),
                        Infolists\Components\ImageEntry::make('photo')
                            ->label(__('Photo'))
                            ->circular(),
                        Infolists\Components\IconEntry::make('active')
                            ->boolean()
                            ->label(__('Active')),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make(__('Owned Courses'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('ownedCourses')
                            ->schema([
                                Infolists\Components\TextEntry::make('code')
                                    ->label(__('Code')),
                                Infolists\Components\TextEntry::make('name')
                                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
                                    ->label(__('Name')),
                                Infolists\Components\IconEntry::make('is_active')
                                    ->boolean()
                                    ->label(__('Active')),
                            ])
                            ->columns(3),
                    ])
                    ->hidden(fn ($record) => $record->ownedCourses->isEmpty()),
                Infolists\Components\Section::make(__('Assigned Courses'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('assignedCourses')
                            ->schema([
                                Infolists\Components\TextEntry::make('code')
                                    ->label(__('Code')),
                                Infolists\Components\TextEntry::make('name')
                                    ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? '')
                                    ->label(__('Name')),
                                Infolists\Components\IconEntry::make('is_active')
                                    ->boolean()
                                    ->label(__('Active')),
                            ])
                            ->columns(3),
                    ])
                    ->hidden(fn ($record) => $record->assignedCourses->isEmpty()),
            ]);
    }
}
