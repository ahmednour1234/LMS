<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?int $navigationSort = 99;

    public static function getNavigationLabel(): string
    {
        return __('navigation.audit_logs');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.audit_log');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.audit_logs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.accounting');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('audit_logs.basic_info'))
                    ->schema([
                        Forms\Components\TextInput::make('action')
                            ->disabled()
                            ->label(__('audit_logs.action')),
                        Forms\Components\TextInput::make('subject_type')
                            ->disabled()
                            ->label(__('audit_logs.subject_type'))
                            ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : ''),
                        Forms\Components\TextInput::make('subject_id')
                            ->disabled()
                            ->label(__('audit_logs.subject_id')),
                        Forms\Components\Select::make('actor_id')
                            ->relationship('actor', 'name')
                            ->disabled()
                            ->label(__('audit_logs.actor'))
                            ->default('System'),
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->disabled()
                            ->label(__('audit_logs.branch'))
                            ->visible(fn () => auth()->user()->isSuperAdmin()),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->disabled()
                            ->label(__('audit_logs.user')),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(__('audit_logs.meta'))
                    ->schema([
                        Forms\Components\KeyValue::make('meta_json')
                            ->disabled()
                            ->label(__('audit_logs.meta_data'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => !empty($record?->meta_json)),
                Forms\Components\Section::make(__('audit_logs.technical_info'))
                    ->schema([
                        Forms\Components\TextInput::make('ip')
                            ->disabled()
                            ->label(__('audit_logs.ip')),
                        Forms\Components\Textarea::make('user_agent')
                            ->disabled()
                            ->label(__('audit_logs.user_agent'))
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->disabled()
                            ->label(__('audit_logs.created_at')),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->label(__('audit_logs.action')),
                Tables\Columns\TextColumn::make('subject_type')
                    ->searchable()
                    ->sortable()
                    ->label(__('audit_logs.subject_type'))
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                Tables\Columns\TextColumn::make('subject_id')
                    ->searchable()
                    ->sortable()
                    ->label(__('audit_logs.subject_id')),
                Tables\Columns\TextColumn::make('actor.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('audit_logs.actor'))
                    ->default('System'),
                Tables\Columns\TextColumn::make('branch.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('audit_logs.branch'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('audit_logs.user'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip')
                    ->searchable()
                    ->label(__('audit_logs.ip'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('meta_json')
                    ->label(__('audit_logs.meta'))
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '-';
                        }
                        return Str::limit(json_encode($state, JSON_PRETTY_PRINT), 100);
                    })
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('audit_logs.created_at')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options(function () {
                        return AuditLog::query()
                            ->distinct()
                            ->pluck('action', 'action')
                            ->toArray();
                    })
                    ->label(__('audit_logs.action')),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->options(function () {
                        return AuditLog::query()
                            ->distinct()
                            ->pluck('subject_type', 'subject_type')
                            ->mapWithKeys(function ($type) {
                                return [$type => class_basename($type)];
                            })
                            ->toArray();
                    })
                    ->label(__('audit_logs.subject_type')),
                Tables\Filters\SelectFilter::make('actor_id')
                    ->relationship('actor', 'name')
                    ->searchable()
                    ->preload()
                    ->label(__('audit_logs.actor')),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->label(__('audit_logs.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('filters.date_from')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('filters.date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->label(__('audit_logs.created_at')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions - audit logs are read-only
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Audit logs are created automatically, not manually
    }

    public static function canEdit($record): bool
    {
        return false; // Audit logs are immutable
    }

    public static function canDelete($record): bool
    {
        return false; // Audit logs should not be deleted
    }
}
