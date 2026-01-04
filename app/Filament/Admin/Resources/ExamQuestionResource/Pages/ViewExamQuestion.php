<?php

namespace App\Filament\Admin\Resources\ExamQuestionResource\Pages;

use App\Filament\Admin\Resources\ExamQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExamQuestion extends ViewRecord
{
    protected static string $resource = ExamQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
