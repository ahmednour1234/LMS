<?php

namespace App\Filament\Teacher\Resources\Training\ExamQuestionResource\Pages;

use App\Filament\Teacher\Resources\Training\ExamQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamQuestions extends ListRecords
{
    protected static string $resource = ExamQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
