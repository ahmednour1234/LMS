<?php

namespace App\Filament\Admin\Resources\TaskSubmissionResource\Pages;

use App\Filament\Admin\Resources\TaskSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaskSubmissions extends ListRecords
{
    protected static string $resource = TaskSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
