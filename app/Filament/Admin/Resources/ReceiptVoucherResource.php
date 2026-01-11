<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\CostCenter;
use App\Domain\Accounting\Models\Voucher;
use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Filament\Admin\Resources\ReceiptVoucherResource\Actions\CancelAction;
use App\Filament\Admin\Resources\ReceiptVoucherResource\Actions\PostAction;
use App\Filament\Admin\Resources\ReceiptVoucherResource\Actions\PrintAction;
use App\Filament\Admin\Resources\ReceiptVoucherResource\Pages;
use App\Filament\Concerns\HasTableExports;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReceiptVoucherResource extends Resource
{
    use HasTableExports;

    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-circle';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('navigation.receipt_vouchers');
    }

    public static function getModelLabel(): string
    {
        return __('vouchers.types.receipt');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.receipt_vouchers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.accounting');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('vouchers.voucher_no'))
                    ->schema([
                        Forms\Components\TextInput::make('voucher_no')
                            ->label(__('vouchers.voucher_no'))
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\DatePicker::make('voucher_date')
                            ->required()
                            ->default(now())
                            ->label(__('vouchers.voucher_date')),
                        Forms\Components\TextInput::make('payee_name')
                            ->label(__('vouchers.payee_name'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('reference_no')
                            ->label(__('vouchers.reference_no'))
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->label(__('vouchers.description')),
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->label(__('vouchers.branch'))
                            ->visible(fn () => auth()->user()->isSuperAdmin()),
                    ])
                    ->columns(2),
                Forms\Components\Repeater::make('voucherLines')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('account_id')
                            ->relationship('account', 'name', fn (Builder $query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label(__('vouchers.account'))
                            ->reactive(),
                        Forms\Components\Select::make('cost_center_id')
                            ->relationship('costCenter', 'name')
                            ->searchable()
                            ->preload()
                            ->label(__('vouchers.cost_center')),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull()
                            ->label(__('vouchers.description')),
                        Forms\Components\TextInput::make('debit')
                            ->numeric()
                            ->default(0)
                            ->label(__('vouchers.debit'))
                            ->reactive(),
                        Forms\Components\TextInput::make('credit')
                            ->numeric()
                            ->default(0)
                            ->label(__('vouchers.credit'))
                            ->reactive(),
                    ])
                    ->columns(2)
                    ->defaultItems(2)
                    ->minItems(2)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['account_id'] ? Account::find($state['account_id'])?->name : null)
                    ->label(__('vouchers.voucher_lines'))
                    ->disabled(fn ($record) => $record && !$record->canBeEdited()),
                Forms\Components\FileUpload::make('attachments')
                    ->multiple()
                    ->disk('local')
                    ->directory('voucher-attachments')
                    ->visibility('private')
                    ->label(__('vouchers.attachments'))
                    ->columnSpanFull(),
            ])
            ->disabled(fn ($record) => $record && !$record->canBeEdited());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('voucher_type', VoucherType::RECEIPT->value);
            })
            ->columns([
                Tables\Columns\TextColumn::make('voucher_no')
                    ->searchable()
                    ->sortable()
                    ->label(__('vouchers.voucher_no')),
                Tables\Columns\TextColumn::make('voucher_date')
                    ->date()
                    ->sortable()
                    ->label(__('vouchers.voucher_date')),
                Tables\Columns\TextColumn::make('payee_name')
                    ->searchable()
                    ->label(__('vouchers.payee_name')),
                Tables\Columns\TextColumn::make('total_debit')
                    ->money()
                    ->sortable()
                    ->label(__('vouchers.total_debit')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (VoucherStatus $state): string => __('vouchers.status_options.' . $state->value))
                    ->color(function (VoucherStatus|string $state): string {
                        $value = $state instanceof VoucherStatus ? $state->value : $state;
                        return match ($value) {
                            'draft' => 'gray',
                            'posted' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->label(__('vouchers.status')),
                Tables\Columns\TextColumn::make('creator.name')
                    ->sortable()
                    ->label(__('vouchers.created_by')),
                Tables\Columns\TextColumn::make('approver.name')
                    ->sortable()
                    ->label(__('vouchers.approved_by'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable()
                    ->label(__('vouchers.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => __('vouchers.status_options.draft'),
                        'posted' => __('vouchers.status_options.posted'),
                        'cancelled' => __('vouchers.status_options.cancelled'),
                    ])
                    ->label(__('vouchers.status')),
                Filter::make('voucher_date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('voucher_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('voucher_date', '<=', $date),
                            );
                    })
                    ->label(__('vouchers.voucher_date')),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label(__('vouchers.branch'))
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Filters\SelectFilter::make('created_by')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->label(__('filters.user')),
            ])
            ->actions([
                PostAction::make(),
                CancelAction::make(),
                PrintAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Voucher $record) => $record->canBeEdited()),
                Tables\Actions\ViewAction::make(),
            ])
            ->headerActions(static::getExportActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $deletableRecords = $records->filter(fn (Voucher $record) => $record->canBeEdited());
                            
                            if ($deletableRecords->isEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title(__('vouchers.errors.cannot_delete'))
                                    ->send();
                                return;
                            }

                            foreach ($deletableRecords as $record) {
                                $record->delete();
                            }

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title(__('vouchers.actions.deleted_success', ['count' => $deletableRecords->count()]))
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceiptVouchers::route('/'),
            'create' => Pages\CreateReceiptVoucher::route('/create'),
            'edit' => Pages\EditReceiptVoucher::route('/{record}/edit'),
        ];
    }
}
