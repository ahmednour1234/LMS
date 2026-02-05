<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Booking\Models\CourseBookingRequest;
use App\Filament\Admin\Resources\CourseBookingRequestResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseBookingRequestResource extends Resource
{
    protected static ?string $model = CourseBookingRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $navigationGroup = 'system';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('navigation.course_booking_requests');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.course_booking_requests');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.course_booking_requests');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.system');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('course_booking_requests.request_details'))
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->label(__('course_booking_requests.full_name'))
                            ->disabled(),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('course_booking_requests.phone'))
                            ->disabled(),
                        Forms\Components\TextInput::make('educational_stage')
                            ->label(__('course_booking_requests.educational_stage'))
                            ->disabled(),
                        Forms\Components\Select::make('gender')
                            ->label(__('course_booking_requests.gender'))
                            ->options([
                                'male' => __('course_booking_requests.genders.male'),
                                'female' => __('course_booking_requests.genders.female'),
                            ])
                            ->disabled(),
                        Forms\Components\Textarea::make('message')
                            ->label(__('course_booking_requests.message'))
                            ->rows(4)
                            ->nullable()
                            ->disabled(),
                        Forms\Components\Select::make('course_id')
                            ->label(__('Course'))
                            ->relationship('course', 'code')
                            ->searchable()
                            ->preload()
                            ->disabled(),
                        Forms\Components\TextInput::make('created_at')
                            ->label(__('course_booking_requests.created_at'))
                            ->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i:s'))
                            ->disabled(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(__('course_booking_requests.admin_section'))
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('course_booking_requests.status'))
                            ->options([
                                'new' => __('course_booking_requests.statuses.new'),
                                'in_progress' => __('course_booking_requests.statuses.in_progress'),
                                'contacted' => __('course_booking_requests.statuses.contacted'),
                                'closed' => __('course_booking_requests.statuses.closed'),
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label(__('course_booking_requests.admin_notes'))
                            ->rows(4)
                            ->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable()
                    ->sortable()
                    ->label(__('course_booking_requests.full_name')),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->label(__('course_booking_requests.phone')),
                Tables\Columns\TextColumn::make('educational_stage')
                    ->searchable()
                    ->label(__('course_booking_requests.educational_stage')),
                Tables\Columns\TextColumn::make('course.code')
                    ->label(__('Course'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'male' => 'info',
                        'female' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => __('course_booking_requests.genders.' . $state))
                    ->label(__('course_booking_requests.gender')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'warning',
                        'in_progress' => 'info',
                        'contacted' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => __('course_booking_requests.statuses.' . $state))
                    ->label(__('course_booking_requests.status')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('course_booking_requests.created_at')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => __('course_booking_requests.statuses.new'),
                        'in_progress' => __('course_booking_requests.statuses.in_progress'),
                        'contacted' => __('course_booking_requests.statuses.contacted'),
                        'closed' => __('course_booking_requests.statuses.closed'),
                    ])
                    ->label(__('course_booking_requests.status')),
                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'male' => __('course_booking_requests.genders.male'),
                        'female' => __('course_booking_requests.genders.female'),
                    ])
                    ->label(__('course_booking_requests.gender')),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('Created From')),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('Created Until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('change_status')
                        ->label(__('Change Status'))
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label(__('course_booking_requests.status'))
                                ->options([
                                    'new' => __('course_booking_requests.statuses.new'),
                                    'in_progress' => __('course_booking_requests.statuses.in_progress'),
                                    'contacted' => __('course_booking_requests.statuses.contacted'),
                                    'closed' => __('course_booking_requests.statuses.closed'),
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $records->each(function ($record) use ($data) {
                                $record->update(['status' => $data['status']]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('course_booking_requests.request_details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('full_name')
                            ->label(__('course_booking_requests.full_name')),
                        Infolists\Components\TextEntry::make('phone')
                            ->label(__('course_booking_requests.phone')),
                        Infolists\Components\TextEntry::make('educational_stage')
                            ->label(__('course_booking_requests.educational_stage')),
                        Infolists\Components\TextEntry::make('gender')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'male' => 'info',
                                'female' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => __('course_booking_requests.genders.' . $state))
                            ->label(__('course_booking_requests.gender')),
                        Infolists\Components\TextEntry::make('message')
                            ->label(__('course_booking_requests.message'))
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('course_booking_requests.created_at'))
                            ->dateTime(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make(__('course_booking_requests.course_info'))
                    ->schema([
                        Infolists\Components\TextEntry::make('course.name')
                            ->label(__('Course'))
                            ->formatStateUsing(fn ($state) => $state[app()->getLocale()] ?? $state['ar'] ?? 'N/A')
                            ->placeholder('N/A'),
                    ])
                    ->visible(fn ($record) => $record->course_id !== null),
                Infolists\Components\Section::make(__('course_booking_requests.admin_section'))
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'new' => 'warning',
                                'in_progress' => 'info',
                                'contacted' => 'success',
                                'closed' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => __('course_booking_requests.statuses.' . $state))
                            ->label(__('course_booking_requests.status')),
                        Infolists\Components\TextEntry::make('admin_notes')
                            ->label(__('course_booking_requests.admin_notes'))
                            ->placeholder('N/A')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('Updated At'))
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseBookingRequests::route('/'),
            'view' => Pages\ViewCourseBookingRequest::route('/{record}'),
            'edit' => Pages\EditCourseBookingRequest::route('/{record}/edit'),
        ];
    }
}
