<?php

namespace App\Filament\Teacher\Resources\Training\ExamResource\Pages;

use App\Filament\Teacher\Resources\Training\ExamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;

    protected function afterCreate(): void
    {
        $this->record->total_score = $this->record->computeTotalScore();
        $this->record->save();
    }
}
