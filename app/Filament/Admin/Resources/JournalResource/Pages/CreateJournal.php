<?php

namespace App\Filament\Admin\Resources\JournalResource\Pages;

use App\Filament\Admin\Resources\JournalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !isset($data['branch_id'])) {
            $data['branch_id'] = $user->branch_id;
        }
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;

        // Validate balance
        if (isset($data['journalLines'])) {
            $debitSum = collect($data['journalLines'])->sum('debit');
            $creditSum = collect($data['journalLines'])->sum('credit');

            if (abs($debitSum - $creditSum) > 0.01) {
                throw new \Filament\Support\Exceptions\Halt();
            }
        }

        return $data;
    }
}

