<?php

namespace App\Filament\Admin\Resources\MediaFileResource\Pages;

use App\Filament\Admin\Resources\MediaFileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMediaFile extends CreateRecord
{
    protected static string $resource = MediaFileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !isset($data['branch_id'])) {
            $data['branch_id'] = $user->branch_id;
        }
        $data['user_id'] = $user->id;

        return $data;
    }
}

