<?php

namespace App\Filament\Teacher\Resources\Training\LessonResource\Pages;

use App\Filament\Teacher\Resources\Training\LessonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLessons extends ListRecords
{
    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
