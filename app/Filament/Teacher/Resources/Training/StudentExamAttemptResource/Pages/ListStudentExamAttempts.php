<?php

namespace App\Filament\Teacher\Resources\Training\StudentExamAttemptResource\Pages;

use App\Filament\Teacher\Resources\Training\StudentExamAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentExamAttempts extends ListRecords
{
    protected static string $resource = StudentExamAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}