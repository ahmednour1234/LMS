<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['name'] = [
            'ar' => $data['name']['ar'] ?? '',
            'en' => $data['name']['en'] ?? '',
        ];

        $data['description'] = [
            'ar' => $data['description']['ar'] ?? null,
            'en' => $data['description']['en'] ?? null,
        ];

        $data['image'] = [
            'ar' => $data['image']['ar'] ?? null,
            'en' => $data['image']['en'] ?? null,
        ];

        return $data;
    }
}
