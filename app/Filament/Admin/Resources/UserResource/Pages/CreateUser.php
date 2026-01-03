<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !isset($data['branch_id'])) {
            $data['branch_id'] = $user->branch_id;
        }
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;

        return $data;
    }
}
