<?php

namespace App\Filament\Admin\Resources\ExamQuestionResource\Pages;

use App\Filament\Admin\Resources\ExamQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExamQuestion extends EditRecord
{
    protected static string $resource = ExamQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
