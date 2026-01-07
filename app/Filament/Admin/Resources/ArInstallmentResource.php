<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Accounting\Models\ArInstallment;
use App\Filament\Admin\Resources\ArInstallmentResource\Pages;
use App\Filament\Concerns\HasTableExports;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArInstallmentResource extends Resource
{
    use HasTableExports;
    protected static ?string $model = ArInstallment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'accounting';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('navigation.installments');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.installment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.installments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.accounting');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('ar_invoice_id')
                    ->relationship(
                        name: 'arInvoice',
                        titleAttribute: 'id',
                        modifyQueryUsing: function (Builder $query) {
                            $user = auth()->user();
                            if (!$user->isSuperAdmin()) {
                                $query->where('branch_id', $user->branch_id);
                            }
                            return $query;
                        }
                    )
                    ->getOptionLabelUsing(function ($value) {
                        $invoice = \App\Domain\Accounting\Models\ArInvoice::with('enrollment.student', 'enrollment.course')->find($value);
                        if (!$invoice) return '';
                        $studentName = $invoice->enrollment->student->name ?? '';
                        $courseName = is_array($invoice->enrollment->course->name ?? null) 
                            ? ($invoice->enrollment->course->name[app()->getLocale()] ?? $invoice->enrollment->course->name['ar'] ?? '') 
                            : ($invoice->enrollment->course->name ?? '');
                        return 'Invoice #' . $invoice->id . ' - ' . $studentName . ' - ' . $courseName;
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('installments.ar_invoice')),
                Forms\Components\TextInput::make('installment_no')
                    ->numeric()
                    ->required()
                    ->label(__('installments.installment_no')),
                Forms\Components\DatePicker::make('due_date')
                    ->required()
                    ->label(__('installments.due_date')),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->label(__('installments.amount')),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => __('installments.status_options.pending'),
                        'paid' => __('installments.status_options.paid'),
                        'overdue' => __('installments.status_options.overdue'),
                    ])
                    ->default('pending')
                    ->required()
                    ->label(__('installments.status')),
                Forms\Components\TextInput::make('paid_amount')
                    ->numeric()
                    ->default(0)
                    ->label(__('installments.paid_amount')),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label(__('installments.paid_at')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user->isSuperAdmin()) {
                    $query->whereHas('arInvoice', function ($q) use ($user) {
                        $q->where('branch_id', $user->branch_id)
                            ->where('user_id', $user->id);
                    });
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('arInvoice.enrollment.student.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('installments.student')),
                Tables\Columns\TextColumn::make('arInvoice.id')
                    ->numeric()
                    ->sortable()
                    ->label(__('installments.invoice_id')),
                Tables\Columns\TextColumn::make('installment_no')
                    ->numeric()
                    ->sortable()
                    ->label(__('installments.installment_no')),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->label(__('installments.due_date'))
                    ->color(fn ($record) => $record->due_date < now() && $record->status !== 'paid' ? 'danger' : null),
                Tables\Columns\TextColumn::make('amount')
                    ->money('OMR')
                    ->sortable()
                    ->label(__('installments.amount')),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->money('OMR')
                    ->label(__('installments.paid_amount')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('installments.status_options.' . $state))
                    ->color(fn ($state) => match($state) {
                        'paid' => 'success',
                        'overdue' => 'danger',
                        default => 'gray',
                    })
                    ->label(__('installments.status')),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('installments.paid_at'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('branch_id')
                    ->form([
                        Forms\Components\Select::make('branch_id')
                            ->options(function () {
                                return \App\Domain\Branch\Models\Branch::query()
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->label(__('filters.branch')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['branch_id'],
                            fn (Builder $query, $branchId): Builder => $query->whereHas('arInvoice', function ($q) use ($branchId) {
                                $q->where('branch_id', $branchId);
                            })
                        );
                    })
                    ->visible(fn () => auth()->user()->isSuperAdmin())
                    ->label(__('filters.branch')),
                Filter::make('user_id')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->options(function () {
                                return \App\Models\User::query()
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->label(__('filters.user')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['user_id'],
                            fn (Builder $query, $userId): Builder => $query->whereHas('arInvoice', function ($q) use ($userId) {
                                $q->where('user_id', $userId);
                            })
                        );
                    })
                    ->label(__('filters.user')),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => __('installments.status_options.pending'),
                        'paid' => __('installments.status_options.paid'),
                        'overdue' => __('installments.status_options.overdue'),
                    ])
                    ->label(__('installments.status')),
                Tables\Filters\Filter::make('overdue')
                    ->label(__('installments.overdue'))
                    ->query(fn (Builder $query): Builder => $query->where('due_date', '<', now())
                        ->where('status', '!=', 'paid')),
                Filter::make('date_range')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date)
                            );
                    })
                    ->label(__('filters.date_range')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('print')
                    ->label(__('exports.print'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (ArInstallment $record) => route('installments.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('qr_code')
                    ->label(__('installments.qr_code'))
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->modalHeading(__('installments.qr_code'))
                    ->modalContent(function (ArInstallment $record) {
                        $publicUrl = route('public.installment.show', ['id' => $record->id]);
                        $qrCodeService = app(\App\Services\QrCodeService::class);
                        $qrCodeSvg = $qrCodeService->generateSvg($publicUrl);
                        
                        return new \Illuminate\Support\HtmlString('
                            <div class="p-4">
                                <div class="flex flex-col items-center space-y-4">
                                    <div class="bg-white p-4 rounded-lg border">
                                        ' . $qrCodeSvg . '
                                    </div>
                                    <div class="text-center w-full">
                                        <p class="text-sm font-medium mb-2">' . __('installments.public_link') . '</p>
                                        <div class="flex items-center space-x-2">
                                            <input type="text" 
                                                   value="' . htmlspecialchars($publicUrl) . '" 
                                                   readonly 
                                                   class="flex-1 px-3 py-2 border rounded-md text-sm"
                                                   id="public-url-' . $record->id . '">
                                            <button onclick="navigator.clipboard.writeText(\'' . htmlspecialchars($publicUrl, ENT_QUOTES) . '\').then(() => alert(\'' . htmlspecialchars(__('installments.copied'), ENT_QUOTES) . '\'))" 
                                                    class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 text-sm">
                                                ' . __('installments.copy') . '
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ');
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('installments.close')),
            ])
            ->headerActions(static::getExportActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArInstallments::route('/'),
            'create' => Pages\CreateArInstallment::route('/create'),
            'view' => Pages\ViewArInstallment::route('/{record}'),
            'edit' => Pages\EditArInstallment::route('/{record}/edit'),
        ];
    }
}
