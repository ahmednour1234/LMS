<?php

namespace App\Filament\Admin\Resources\TaskSubmissionResource\Pages;

use App\Filament\Admin\Resources\TaskSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaskSubmission extends EditRecord
{
    protected static string $resource = TaskSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
