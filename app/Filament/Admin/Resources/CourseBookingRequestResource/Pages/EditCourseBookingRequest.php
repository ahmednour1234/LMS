<?php

namespace App\Filament\Admin\Resources\CourseBookingRequestResource\Pages;

use App\Filament\Admin\Resources\CourseBookingRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourseBookingRequest extends EditRecord
{
    protected static string $resource = CourseBookingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
