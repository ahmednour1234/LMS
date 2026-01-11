<?php

namespace App\Filament\Admin\Resources\ReceiptVoucherResource\Actions;

use App\Domain\Accounting\Models\Voucher;
use App\Domain\Accounting\Services\VoucherPostingService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class CancelAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cancel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('vouchers.actions.cancel'));
        $this->icon('heroicon-o-x-circle');
        $this->color('danger');
        $this->requiresConfirmation();
        $this->modalHeading(__('vouchers.actions.cancel_confirm'));
        $this->modalSubmitActionLabel(__('vouchers.actions.cancel_confirm_button'));

        $this->action(function (Voucher $record) {
            try {
                $service = app(VoucherPostingService::class);
                $service->cancel($record, auth()->user());

                Notification::make()
                    ->success()
                    ->title(__('vouchers.actions.cancelled_success'))
                    ->send();
            } catch (\RuntimeException $e) {
                Notification::make()
                    ->danger()
                    ->title(__('vouchers.errors.cannot_cancel'))
                    ->body($e->getMessage())
                    ->send();
            }
        });

        $this->visible(function (Voucher $record) {
            return $record->isPosted()
                && auth()->user()->can('cancel', $record);
        });
    }
}
