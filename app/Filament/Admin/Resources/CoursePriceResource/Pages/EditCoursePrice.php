<?php

namespace App\Filament\Admin\Resources\CoursePriceResource\Pages;

use App\Filament\Admin\Resources\CoursePriceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCoursePrice extends EditRecord
{
    protected static string $resource = CoursePriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
