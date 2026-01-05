<?php

namespace App\Filament\Admin\Resources\JournalResource\Pages;

use App\Domain\Accounting\Models\Journal;
use App\Enums\JournalStatus;
use App\Filament\Admin\Resources\JournalResource;
use App\Filament\Admin\Resources\JournalResource\Actions\PostAction;
use App\Filament\Admin\Resources\JournalResource\Actions\PrintAction;
use App\Filament\Admin\Resources\JournalResource\Actions\VoidAction;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditJournal extends EditRecord
{
    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PostAction::make(),
            VoidAction::make(),
            PrintAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->canBeEdited() && !$this->record->isPosted()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        // Validate balance
        if (isset($data['journalLines'])) {
            $debitSum = collect($data['journalLines'])->sum('debit');
            $creditSum = collect($data['journalLines'])->sum('credit');

            if (abs($debitSum - $creditSum) > 0.01) {
                Notification::make()
                    ->danger()
                    ->title(__('journals.errors.imbalanced'))
                    ->body(__('journals.errors.debit_credit_mismatch', [
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

        if ($this->record->status === JournalStatus::POSTED || $this->record->status === JournalStatus::VOID) {
            $this->form->disabled();
        }
    }
}

