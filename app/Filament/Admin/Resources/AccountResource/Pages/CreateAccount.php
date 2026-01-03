<?php

namespace App\Filament\Admin\Resources\AccountResource\Pages;

use App\Filament\Admin\Resources\AccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !isset($data['branch_id'])) {
            $data['branch_id'] = $user->branch_id;
        }

        return $data;
    }
}

