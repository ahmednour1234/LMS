<?php

namespace App\Filament\Admin\Resources\ReceiptVoucherResource\Actions;

use App\Domain\Accounting\Models\Voucher;
use App\Domain\Accounting\Services\VoucherPostingService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;

class PostAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'post';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('vouchers.actions.post'));
        $this->icon('heroicon-o-check-circle');
        $this->color('success');
        $this->requiresConfirmation();
        $this->modalHeading(__('vouchers.actions.post_confirm'));
        $this->modalSubmitActionLabel(__('vouchers.actions.post_confirm_button'));

        $this->action(function (Voucher $record) {
            try {
                $service = app(VoucherPostingService::class);
                $service->post($record, auth()->user());

                Notification::make()
                    ->success()
                    ->title(__('vouchers.actions.posted_success'))
                    ->send();
            } catch (\RuntimeException $e) {
                Notification::make()
                    ->danger()
                    ->title(__('vouchers.errors.cannot_post'))
                    ->body($e->getMessage())
                    ->send();
            }
        });

        $this->visible(function (Voucher $record) {
            return $record->isDraft()
                && auth()->user()->can('post', $record);
        });
    }
}
