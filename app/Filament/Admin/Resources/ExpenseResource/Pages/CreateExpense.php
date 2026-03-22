<?php

namespace App\Filament\Admin\Resources\ExpenseResource\Pages;

use App\Filament\Admin\Resources\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin()) {
            $data['branch_id'] = $user->branch_id;
        }
        if (empty($data['user_id'])) {
            $data['user_id'] = $user?->id;
        }

        return $data;
    }
}
