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
        if (empty($data['cash_bank_account_id']) || empty($data['counterparty_account_id'])) {
            Notification::make()
                ->danger()
                ->title(__('validation.required'))
                ->body(__('vouchers.errors.accounts_required'))
                ->send();

            throw new \Filament\Support\Exceptions\Halt();
        }

        if (empty($data['amount']) || (float) $data['amount'] <= 0) {
            Notification::make()
                ->danger()
                ->title(__('validation.required'))
                ->body(__('vouchers.errors.amount_required'))
                ->send();

            throw new \Filament\Support\Exceptions\Halt();
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
