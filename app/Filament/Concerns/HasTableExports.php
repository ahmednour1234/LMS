<?php

namespace App\Filament\Concerns;

use App\Services\TableExportService;
use Filament\Tables\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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
                    $columns = collect($livewire->getTable()->getColumns())->filter(fn ($col) => ! $col->isHidden());
                    $filename = $resourceName . '_' . now()->format('Y-m-d_His');
                    
                    // Get records and store in cache (limit to prevent memory issues)
                    $records = $query->limit(10000)->get();
                    
                    // Extract serializable column metadata
                    $columnData = $columns->map(function ($column) {
                        return [
                            'name' => is_callable([$column, 'getName']) ? $column->getName() : ($column->getName ?? ''),
                            'label' => is_callable([$column, 'getLabel']) ? $column->getLabel() : ($column->getLabel ?? ''),
                        ];
                    })->toArray();
                    
                    // Store export data in cache with token
                    $token = Str::random(32);
                    Cache::put("export_excel_{$token}", [
                        'records' => $records,
                        'columns' => $columnData,
                        'filename' => $filename,
                    ], now()->addMinutes(5));
                    
                    // Redirect to download route
                    return redirect()->route('filament.admin.exports.excel', ['token' => $token]);
                }),

            Action::make('exportPdf')
                ->label(__('exports.pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function (HasTable $livewire) use ($resourceName, $title) {
                    $tableExportService = app(TableExportService::class);
                    $query = $tableExportService->buildQueryFromTableState($livewire);
                    $columns = collect($livewire->getTable()->getColumns())->filter(fn ($col) => ! $col->isHidden());
                    $filename = $resourceName . '_' . now()->format('Y-m-d_His');
                    
                    // Get records and store in cache (limit to prevent memory issues)
                    $records = $query->limit(10000)->get();
                    
                    // Extract serializable column metadata
                    $columnData = $columns->map(function ($column) {
                        return [
                            'name' => is_callable([$column, 'getName']) ? $column->getName() : ($column->getName ?? ''),
                            'label' => is_callable([$column, 'getLabel']) ? $column->getLabel() : ($column->getLabel ?? ''),
                        ];
                    })->toArray();
                    
                    // Store export data in cache with token
                    $token = Str::random(32);
                    Cache::put("export_pdf_{$token}", [
                        'records' => $records,
                        'columns' => $columnData,
                        'filename' => $filename,
                        'title' => $title,
                    ], now()->addMinutes(5));
                    
                    // Redirect to download route
                    return redirect()->route('filament.admin.exports.pdf', ['token' => $token]);
                }),

            Action::make('print')
                ->label(__('exports.print'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function (HasTable $livewire) use ($title) {
                    $tableExportService = app(TableExportService::class);
                    $query = $tableExportService->buildQueryFromTableState($livewire);
                    $columns = collect($livewire->getTable()->getColumns())->filter(fn ($col) => ! $col->isHidden());
                    
                    // Get records and store in cache (limit to prevent memory issues)
                    $records = $query->limit(10000)->get();
                    
                    // Extract serializable column metadata
                    $columnData = $columns->map(function ($column) {
                        return [
                            'name' => is_callable([$column, 'getName']) ? $column->getName() : ($column->getName ?? ''),
                            'label' => is_callable([$column, 'getLabel']) ? $column->getLabel() : ($column->getLabel ?? ''),
                        ];
                    })->toArray();
                    
                    // Store export data in cache with token
                    $token = Str::random(32);
                    Cache::put("export_print_{$token}", [
                        'records' => $records,
                        'columns' => $columnData,
                        'title' => $title,
                    ], now()->addMinutes(5));
                    
                    // Redirect to print route
                    return redirect()->route('filament.admin.exports.print', ['token' => $token]);
                })
                ->openUrlInNewTab(),
        ];
    }
}

