<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Accounting\Models\Expense;
use App\Filament\Admin\Resources\ExpenseResource\Pages;
use App\Filament\Concerns\HasTableExports;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExpenseResource extends Resource
{
    use HasTableExports;

    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('navigation.expenses');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.expense');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.expenses');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.accounting');
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('payment_method_id')
                    ->relationship('paymentMethod', 'name', fn (Builder $query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('expenses.payment_method')),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->prefix('OMR')
                    ->label(__('expenses.amount')),
                Forms\Components\DatePicker::make('expense_date')
                    ->required()
                    ->default(now())
                    ->label(__('expenses.expense_date')),
                Forms\Components\Textarea::make('notes')
                    ->rows(3)
                    ->label(__('expenses.notes')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                /** @var \App\Models\User|null $user */
                $user = Auth::user();
                if ($user && !$user->isSuperAdmin()) {
                    $query->where('branch_id', $user->branch_id)
                        ->where('user_id', $user->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('expenses.payment_method')),
                Tables\Columns\TextColumn::make('amount')
                    ->money('OMR')
                    ->sortable()
                    ->label(__('expenses.amount')),
                Tables\Columns\TextColumn::make('expense_date')
                    ->date()
                    ->sortable()
                    ->label(__('expenses.expense_date')),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(50)
                    ->label(__('expenses.notes')),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('expenses.user')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method_id')
                    ->relationship('paymentMethod', 'name')
                    ->searchable()
                    ->label(__('expenses.payment_method')),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->label(__('filters.branch'))
                    ->visible(function () {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        return $user?->isSuperAdmin() ?? false;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
