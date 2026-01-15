<?php

namespace App\Filament\Admin\Resources\CourseSessionResource\Pages;

use App\Filament\Admin\Resources\CourseSessionResource;
use App\Http\Services\CourseSessionService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\App;

class EditCourseSession extends EditRecord
{
    protected static string $resource = CourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $service = App::make(CourseSessionService::class);
        $record = $this->getRecord();
        
        if (isset($data['provider']) && $data['provider'] === 'jitsi' && !$record->room_slug) {
            $data['room_slug'] = $service->generateRoomSlug();
        }

        if (isset($data['location_type']) && $data['location_type'] === 'onsite' && !$record->onsite_qr_secret) {
            $data['onsite_qr_secret'] = $service->generateQrSecret();
        }

        return $data;
    }
}
