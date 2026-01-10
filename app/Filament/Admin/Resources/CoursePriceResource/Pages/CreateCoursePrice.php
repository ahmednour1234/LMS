<?php

namespace App\Filament\Admin\Resources\CoursePriceResource\Pages;

use App\Filament\Admin\Resources\CoursePriceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCoursePrice extends CreateRecord
{
    protected static string $resource = CoursePriceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['branch_id'] = null;
        return $data;
    }
}
