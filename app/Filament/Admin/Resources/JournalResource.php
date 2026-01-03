<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\CostCenter;
use App\Domain\Accounting\Models\Journal;
use App\Enums\JournalStatus;
use App\Filament\Admin\Resources\JournalResource\Pages;
use App\Filament\Concerns\HasTableExports;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JournalResource extends Resource
{
    use HasTableExports;
    protected static ?string $model = Journal::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'navigation.journals';

    protected static ?string $pluralModelLabel = 'navigation.journals';

    public static function getNavigationLabel(): string
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
                Forms\Components\DatePicker::make('date')
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
                    ])
                    ->default('draft')
                    ->required()
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
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->label(__('journal_lines.description')),
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
                    ->label(__('journals.journal_lines')),
            ]);
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
                Tables\Columns\TextColumn::make('date')
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
                            default => 'gray',
                        };
                    })
                    ->label(__('journals.status')),
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
                    ])
                    ->label(__('journals.status')),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label(__('journals.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions(static::getExportActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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

