<?php

namespace App\Filament\Teacher\Resources\Training\SessionResource\Pages;

use App\Filament\Teacher\Resources\Training\SessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSession extends EditRecord
{
    protected static string $resource = SessionResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->record->teacher_id !== auth('teacher')->id()) {
            abort(404);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
