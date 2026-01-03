<?php

namespace App\Filament\Admin\Resources\MediaFileResource\Pages;

use App\Filament\Admin\Resources\MediaFileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMediaFile extends EditRecord
{
    protected static string $resource = MediaFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

