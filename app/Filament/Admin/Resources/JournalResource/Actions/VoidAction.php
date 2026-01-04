<?php

namespace App\Filament\Admin\Resources\JournalResource\Actions;

use App\Domain\Accounting\Models\Journal;
use App\Enums\JournalStatus;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;

class VoidAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'void';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('journals.actions.void'));
        $this->icon('heroicon-o-x-circle');
        $this->color('danger');
        $this->requiresConfirmation();
        $this->modalHeading(__('journals.actions.void_confirm'));
        $this->modalSubmitActionLabel(__('journals.actions.void_confirm_button'));

        $this->action(function (Journal $record) {
            DB::transaction(function () use ($record) {
                $record->update([
                    'status' => JournalStatus::VOID,
                ]);
            });

            Notification::make()
                ->success()
                ->title(__('journals.actions.voided_success'))
                ->send();
        });

        $this->visible(function (Journal $record) {
            return $record->status === JournalStatus::POSTED
                && auth()->user()->can('void', $record);
        });
    }
}

