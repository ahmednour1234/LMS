<?php

namespace App\Filament\Concerns;

use App\Services\TableExportService;
use Filament\Tables\Actions\Action;
use Filament\Tables\Contracts\HasTable;

trait HasTableExports
{
    /**
     * Get export actions (Excel, PDF, Print) for table header
     * Use this in your Resource's table() method:
     * ->headerActions(array_merge([...existing actions...], static::getExportActions()))
     *
     * @return array
     */
    public static function getExportActions(): array
    {
        $resourceClass = static::class;
        $resourceName = class_basename($resourceClass);
        $title = static::getModelLabel() ?? $resourceName;

        return [
            Action::make('exportExcel')
                ->label(__('exports.excel'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (HasTable $livewire) use ($resourceName, $title) {
                    $tableExportService = app(TableExportService::class);
                    $query = $tableExportService->buildQueryFromTableState($livewire);
                    $columns = $livewire->getTable()->getColumns()->filter(fn ($col) => ! $col->isHidden());
                    $filename = $resourceName . '_' . now()->format('Y-m-d_His');
                    return $tableExportService->exportXlsx($query, $columns, $filename);
                }),

            Action::make('exportPdf')
                ->label(__('exports.pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function (HasTable $livewire) use ($resourceName, $title) {
                    $tableExportService = app(TableExportService::class);
                    $query = $tableExportService->buildQueryFromTableState($livewire);
                    $columns = $livewire->getTable()->getColumns()->filter(fn ($col) => ! $col->isHidden());
                    $filename = $resourceName . '_' . now()->format('Y-m-d_His');
                    return $tableExportService->exportPdf($query, $columns, $filename, $title);
                }),

            Action::make('print')
                ->label(__('exports.print'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function (HasTable $livewire) use ($title) {
                    $tableExportService = app(TableExportService::class);
                    $query = $tableExportService->buildQueryFromTableState($livewire);
                    $columns = $livewire->getTable()->getColumns()->filter(fn ($col) => ! $col->isHidden());
                    
                    // Render print view and return with JavaScript to print
                    $html = $tableExportService->renderPrint($query, $columns, $title)->render();
                    
                    // Return HTML with auto-print script
                    return response($html . '<script>window.onload = function() { window.print(); }</script>')
                        ->header('Content-Type', 'text/html');
                })
                ->openUrlInNewTab(),
        ];
    }
}

