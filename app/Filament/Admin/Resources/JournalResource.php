<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\CostCenter;
use App\Domain\Accounting\Models\Journal;
use App\Enums\JournalStatus;
use App\Filament\Admin\Resources\JournalResource\Actions\PostAction;
use App\Filament\Admin\Resources\JournalResource\Actions\PrintAction;
use App\Filament\Admin\Resources\JournalResource\Actions\VoidAction;
use App\Filament\Admin\Resources\JournalResource\Pages;
use App\Filament\Concerns\HasTableExports;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class JournalResource extends Resource
{
    use HasTableExports;
    protected static ?string $model = Journal::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('navigation.journals');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.journals');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.journals');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.accounting');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->label(__('journals.reference')),
                Forms\Components\DatePicker::make('journal_date')
                    ->required()
                    ->default(now())
                    ->label(__('journals.date')),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull()
                    ->label(__('journals.description')),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => __('journals.status_options.draft'),
                        'posted' => __('journals.status_options.posted'),
                        'void' => __('journals.status_options.void'),
                    ])
                    ->default('draft')
                    ->required()
                    ->disabled(fn ($record) => $record && ($record->status === JournalStatus::POSTED || $record->status === JournalStatus::VOID))
                    ->label(__('journals.status')),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->label(__('journals.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Forms\Components\Repeater::make('journalLines')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('account_id')
                            ->relationship('account', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label(__('journal_lines.account')),
                        Forms\Components\TextInput::make('debit')
                            ->numeric()
                            ->default(0)
                            ->label(__('journal_lines.debit')),
                        Forms\Components\TextInput::make('credit')
                            ->numeric()
                            ->default(0)
                            ->label(__('journal_lines.credit')),
                        Forms\Components\Textarea::make('memo')
                            ->rows(2)
                            ->label(__('journal_lines.memo')),
                        Forms\Components\Select::make('cost_center_id')
                            ->relationship('costCenter', 'name')
                            ->searchable()
                            ->preload()
                            ->label(__('journal_lines.cost_center')),
                    ])
                    ->columns(2)
                    ->defaultItems(2)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['account_id'] ? Account::find($state['account_id'])?->name : null)
                    ->label(__('journals.journal_lines'))
                    ->disabled(fn ($record) => $record && ($record->status === JournalStatus::POSTED || $record->status === JournalStatus::VOID))
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                        // Validate balance on save
                        return $data;
                    }),
            ])
            ->disabled(fn ($record) => $record && ($record->status === JournalStatus::POSTED || $record->status === JournalStatus::VOID));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->visibleTo(auth()->user(), 'journals');
            })
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->label(__('journals.reference')),
                Tables\Columns\TextColumn::make('journal_date')
                    ->date()
                    ->sortable()
                    ->label(__('journals.date')),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->label(__('journals.description')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (JournalStatus $state): string => __('journals.status_options.' . $state->value))
                    ->color(function (JournalStatus|string $state): string {
                        $value = $state instanceof JournalStatus ? $state->value : $state;
                        return match ($value) {
                            'draft' => 'gray',
                            'posted' => 'success',
                            'void' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->label(__('journals.status')),
                Tables\Columns\TextColumn::make('poster.name')
                    ->sortable()
                    ->label(__('journals.posted_by'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable()
                    ->label(__('journals.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Columns\TextColumn::make('creator.name')
                    ->sortable()
                    ->label(__('journals.created_by')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => __('journals.status_options.draft'),
                        'posted' => __('journals.status_options.posted'),
                        'void' => __('journals.status_options.void'),
                    ])
                    ->label(__('journals.status')),
                Filter::make('journal_date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('journal_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('journal_date', '<=', $date),
                            );
                    })
                    ->label(__('journals.date')),
                Tables\Filters\SelectFilter::make('reference_type')
                    ->options(function () {
                        return Journal::query()
                            ->whereNotNull('reference_type')
                            ->distinct()
                            ->pluck('reference_type', 'reference_type')
                            ->toArray();
                    })
                    ->label(__('journals.reference_type')),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label(__('journals.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->label(__('filters.user')),
            ])
            ->actions([
                PostAction::make(),
                VoidAction::make(),
                PrintAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Journal $record) => $record->canBeEdited() && !$record->isPosted()),
            ])
            ->headerActions(static::getExportActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => true)
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            // Filter out posted journals
                            $deletableRecords = $records->filter(fn (Journal $record) => !$record->isPosted());
                            
                            if ($deletableRecords->isEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title(__('journals.errors.cannot_delete_posted'))
                                    ->send();
                                return;
                            }

                            foreach ($deletableRecords as $record) {
                                $record->delete();
                            }

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title(__('journals.actions.deleted_success', ['count' => $deletableRecords->count()]))
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'edit' => Pages\EditJournal::route('/{record}/edit'),
        ];
    }

}

