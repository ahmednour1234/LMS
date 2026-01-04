<?php

namespace App\Filament\Admin\Resources\JournalResource\Actions;

use App\Services\PdfService;
use App\Domain\Accounting\Models\Journal;
use Filament\Tables\Actions\Action;

class PrintAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'print';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('journals.actions.print'));
        $this->icon('heroicon-o-printer');
        $this->color('gray');
        $this->url(fn (Journal $record): string => route('journals.print', $record));
        $this->openUrlInNewTab();
    }
}

