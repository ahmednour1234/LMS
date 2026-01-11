<?php

namespace App\Filament\Admin\Resources\ReceiptVoucherResource\Pages;

use App\Domain\Accounting\Models\Voucher;
use App\Domain\Accounting\Services\VoucherPostingService;
use App\Enums\VoucherStatus;
use App\Filament\Admin\Resources\ReceiptVoucherResource;
use App\Filament\Admin\Resources\ReceiptVoucherResource\Actions\CancelAction;
use App\Filament\Admin\Resources\ReceiptVoucherResource\Actions\PostAction;
use App\Filament\Admin\Resources\ReceiptVoucherResource\Actions\PrintAction;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditReceiptVoucher extends EditRecord
{
    protected static string $resource = ReceiptVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PostAction::make(),
            CancelAction::make(),
            PrintAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->canBeEdited()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['voucherLines'])) {
            $debitSum = collect($data['voucherLines'])->sum('debit');
            $creditSum = collect($data['voucherLines'])->sum('credit');

            if (abs($debitSum - $creditSum) > 0.01) {
                Notification::make()
                    ->danger()
                    ->title(__('vouchers.errors.imbalanced'))
                    ->body(__('vouchers.errors.debit_credit_mismatch', [
                        'debit' => number_format($debitSum, 2),
                        'credit' => number_format($creditSum, 2),
                    ]))
                    ->send();

                throw new \Filament\Support\Exceptions\Halt();
            }
        }

        return $data;
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->record->status === VoucherStatus::POSTED || $this->record->status === VoucherStatus::CANCELLED) {
            $this->form->disabled();
        }
    }
}
