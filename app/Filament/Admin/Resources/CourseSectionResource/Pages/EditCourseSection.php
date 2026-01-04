<?php

namespace App\Filament\Admin\Resources\CourseSectionResource\Pages;

use App\Filament\Admin\Resources\CourseSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourseSection extends EditRecord
{
    protected static string $resource = CourseSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
