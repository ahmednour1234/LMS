<?php

namespace App\Filament\Admin\Resources\SettingResource\Pages;

use App\Filament\Admin\Resources\SettingResource;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Delete action is disabled for system settings via policy
            // Non-system settings can be deleted by Super Admin via table bulk actions
        ];
    }

    protected function authorizeAccess(): void
    {
        $record = $this->getRecord();

        // System settings can only be edited by Super Admin
        if ($record->isSystemSetting() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Only Super Admin can edit system settings.');
        }

        parent::authorizeAccess();
    }
}

