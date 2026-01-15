<?php

namespace App\Filament\Admin\Resources\CourseSessionResource\Pages;

use App\Domain\Training\Enums\LocationType;
use App\Filament\Admin\Resources\CourseSessionResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCourseSession extends ViewRecord
{
    protected static string $resource = CourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $record = $this->getRecord();
        
        $schema = [
            Infolists\Components\Section::make(__('attendance.session_information'))
                ->schema([
                    Infolists\Components\TextEntry::make('course.code')
                        ->label(__('attendance.course')),
                    Infolists\Components\TextEntry::make('title')
                        ->label(__('attendance.title')),
                    Infolists\Components\TextEntry::make('location_type')
                        ->formatStateUsing(fn ($state) => $state instanceof LocationType
                            ? __('attendance.location_type_options.' . $state->value)
                            : __('attendance.location_type_options.' . $state))
                        ->badge()
                        ->label(__('attendance.location_type')),
                    Infolists\Components\TextEntry::make('starts_at')
                        ->dateTime()
                        ->label(__('attendance.starts_at')),
                    Infolists\Components\TextEntry::make('ends_at')
                        ->dateTime()
                        ->label(__('attendance.ends_at')),
                    Infolists\Components\TextEntry::make('status')
                        ->formatStateUsing(fn ($state) => $state instanceof \App\Domain\Training\Enums\SessionStatus
                            ? __('attendance.status_options.' . $state->value)
                            : __('attendance.status_options.' . $state))
                        ->badge()
                        ->label(__('attendance.status')),
                ])
                ->columns(2),
        ];

        if ($record->location_type === LocationType::ONSITE) {
            $schema[] = Infolists\Components\Section::make(__('attendance.qr_code'))
                ->schema([
                    Infolists\Components\TextEntry::make('onsite_qr_secret')
                        ->label(__('attendance.qr_placeholder'))
                        ->default('SESSION:' . $record->id)
                        ->formatStateUsing(fn () => 'SESSION:' . $record->id),
                ])
                ->description(__('attendance.qr_placeholder_desc'));
        }

        if ($record->provider === 'jitsi' && $record->room_slug) {
            $schema[] = Infolists\Components\Section::make(__('attendance.jitsi_meeting'))
                ->schema([
                    Infolists\Components\TextEntry::make('room_slug')
                        ->label(__('attendance.room_slug')),
                    Infolists\Components\TextEntry::make('join_url')
                        ->label(__('attendance.join_url'))
                        ->default('https://meet.jit.si/' . $record->room_slug)
                        ->formatStateUsing(fn () => 'https://meet.jit.si/' . $record->room_slug)
                        ->url(fn () => 'https://meet.jit.si/' . $record->room_slug),
                ])
                ->description(__('attendance.jitsi_placeholder_desc'));
        }

        return $infolist->schema($schema);
    }
}
