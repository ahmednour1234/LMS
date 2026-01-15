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
            Actions\Action::make('studio')
                ->label(__('Course Studio'))
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->button()
                ->size(Actions\Action::SIZE_LARGE)
                ->url(fn () => CourseResource::getUrl('studio', ['record' => $this->getRecord()]))
                ->visible(fn () => auth()->user()->isSuperAdmin() || auth()->user()->hasRole('admin')),
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Tabs::make('CourseTabs')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make(__('Course Information'))
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
                                    ])
                                    ->columns(1)
                                    ->collapsible(),
                            ]),
                        Infolists\Components\Tabs\Tab::make(__('attendance.sessions_attendance'))
                            ->schema([
                                Infolists\Components\Section::make(__('attendance.sessions_attendance'))
                                    ->schema([
                                        Infolists\Components\TextEntry::make('sessions_count')
                                            ->label(__('attendance.sessions_count'))
                                            ->state(fn ($record) => $record->sessions()->count())
                                            ->suffix(' ' . __('attendance.sessions')),
                                        Infolists\Components\Actions::make([
                                            \Filament\Infolists\Components\Actions\Action::make('manage_sessions')
                                                ->label(__('attendance.manage_sessions'))
                                                ->url(fn ($record) => \App\Filament\Admin\Resources\CourseSessionResource::getUrl('index', ['tableFilters' => ['course_id' => ['value' => $record->id]]]))
                                                ->icon('heroicon-o-calendar-days')
                                                ->color('primary'),
                                        ]),
                                    ])
                                    ->description(__('attendance.sessions_tab_desc')),
                            ]),
                    ]),
            ]);
    }
}

