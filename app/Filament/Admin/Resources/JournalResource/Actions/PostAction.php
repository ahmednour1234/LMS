<?php

namespace App\Filament\Admin\Resources\JournalResource\Actions;

use App\Domain\Accounting\Events\JournalPosted;
use App\Domain\Accounting\Models\Journal;
use App\Enums\JournalStatus;
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

        $this->label(__('journals.actions.post'));
        $this->icon('heroicon-o-check-circle');
        $this->color('success');
        $this->requiresConfirmation();
        $this->modalHeading(__('journals.actions.post_confirm'));
        $this->modalSubmitActionLabel(__('journals.actions.post_confirm_button'));

        $this->action(function (Journal $record) {
            // Validate balance
            $debitSum = $record->journalLines()->sum('debit');
            $creditSum = $record->journalLines()->sum('credit');

            if (abs($debitSum - $creditSum) > 0.01) {
                Notification::make()
                    ->danger()
                    ->title(__('journals.errors.imbalanced'))
                    ->body(__('journals.errors.debit_credit_mismatch', [
                        'debit' => number_format($debitSum, 2),
                        'credit' => number_format($creditSum, 2),
                    ]))
                    ->send();

                return;
            }

            // Check for idempotency (reference_type + reference_id must be unique if both are set)
            if ($record->reference_type && $record->reference_id) {
                $existing = Journal::where('reference_type', $record->reference_type)
                    ->where('reference_id', $record->reference_id)
                    ->where('status', JournalStatus::POSTED)
                    ->where('id', '!=', $record->id)
                    ->exists();

                if ($existing) {
                    Notification::make()
                        ->danger()
                        ->title(__('journals.errors.already_posted'))
                        ->body(__('journals.errors.duplicate_reference'))
                        ->send();

                    return;
                }
            }

            // Post the journal
            DB::transaction(function () use ($record) {
                $record->update([
                    'status' => JournalStatus::POSTED,
                    'posted_by' => auth()->id(),
                    'posted_at' => now(),
                ]);

                // Fire event for audit logging
                event(new JournalPosted($record));
            });

            Notification::make()
                ->success()
                ->title(__('journals.actions.posted_success'))
                ->send();
        });

        $this->visible(function (Journal $record) {
            return $record->status === JournalStatus::DRAFT
                && auth()->user()->can('post', $record);
        });
    }
}

