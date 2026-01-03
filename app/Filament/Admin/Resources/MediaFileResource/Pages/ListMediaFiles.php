<?php

namespace App\Filament\Admin\Resources\MediaFileResource\Pages;

use App\Filament\Admin\Resources\MediaFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMediaFiles extends ListRecords
{
    protected static string $resource = MediaFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

