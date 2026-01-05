<?php

namespace App\Filament\Admin\Resources\EnrollmentResource\Pages;

use App\Filament\Admin\Resources\EnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEnrollment extends CreateRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Reference will be auto-generated in the model boot method
        // Set enrolled_at if not provided
        if (empty($data['enrolled_at'])) {
            $data['enrolled_at'] = now();
        }

        // Set branch_id from user if not provided and user is not super admin
        if (empty($data['branch_id']) && !auth()->user()->isSuperAdmin()) {
            $data['branch_id'] = auth()->user()->branch_id;
        }

        // Set created_by
        $data['created_by'] = auth()->id();

        return $data;
    }
}
