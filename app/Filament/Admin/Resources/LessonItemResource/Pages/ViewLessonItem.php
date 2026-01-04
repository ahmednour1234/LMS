<?php

namespace App\Filament\Admin\Resources\LessonItemResource\Pages;

use App\Filament\Admin\Resources\LessonItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLessonItem extends ViewRecord
{
    protected static string $resource = LessonItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
