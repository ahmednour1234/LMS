<?php

namespace App\Filament\Admin\Resources\LessonItemResource\Pages;

use App\Filament\Admin\Resources\LessonItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLessonItem extends EditRecord
{
    protected static string $resource = LessonItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
