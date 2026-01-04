<?php

namespace App\Filament\Admin\Resources\TaskSubmissionResource\Pages;

use App\Filament\Admin\Resources\TaskSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTaskSubmission extends ViewRecord
{
    protected static string $resource = TaskSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
