<?php

namespace App\Filament\Concerns;

use App\Services\TableExportService;
use Filament\Tables\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait HasTableExports
{
    /**
     * ✅ Get the table query including filters/search/sort (Filament v3)
     */
    protected static function getTableExportQuery(HasTable $livewire): Builder
    {
        // Filament v3 (ListRecords implements this)
        if (method_exists($livewire, 'getFilteredTableQuery')) {
            return $livewire->getFilteredTableQuery();
        }

        // Fallback
        if (method_exists($livewire, 'getTableQuery')) {
            return $livewire->getTableQuery();
        }

        throw new \RuntimeException('Cannot resolve table query for export.');
    }

    /**
     * Extract relationships to eager load based on column names (e.g. course.code -> course)
     */
    protected static function eagerLoadRelationships($records, array $columnData): void
    {
        if (!$records || $records->isEmpty()) {
            return;
        }

        $relationships = collect($columnData)
            ->pluck('name')
            ->filter()
            ->map(fn ($name) => str_contains($name, '.') ? explode('.', $name)[0] : null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($relationships)) {
            $records->loadMissing($relationships);
        }
    }

    /**
     * Make label always string and safe for Excel headers
     */
    protected static function stringifyLabel($label, string $fallback): string
    {
        if ($label instanceof Htmlable) {
            $label = strip_tags($label->toHtml());
        } elseif (is_object($label) && method_exists($label, '__toString')) {
            $label = (string) $label;
        } elseif (!is_string($label)) {
            $label = '';
        }

        $label = trim((string) $label);

        return $label !== '' ? $label : ($fallback !== '' ? $fallback : 'Column');
    }

    /**
     * Serialize visible Filament columns into {name,label} only (cache friendly)
     */
    protected static function buildColumnData(HasTable $livewire): array
    {
        /** @var TableExportService $service */
        $service = app(TableExportService::class);

        // ✅ if your TableExportService has getVisibleColumns() use it
        if (method_exists($service, 'getVisibleColumns')) {
            $columns = $service->getVisibleColumns($livewire);
        } else {
            // fallback: use table columns directly
            $columns = collect($livewire->getTable()->getColumns())
                ->filter(fn ($col) => method_exists($col, 'isHidden') ? ! $col->isHidden() : true)
                ->filter(fn ($col) => method_exists($col, 'getName') && filled($col->getName()));
        }

        return $columns->values()->map(function ($column) {
            $name = method_exists($column, 'getName') ? (string) $column->getName() : '';
            $rawLabel = method_exists($column, 'getLabel') ? $column->getLabel() : '';

            $label = static::stringifyLabel($rawLabel, $name);

            return [
                'name'  => $name,
                'label' => $label,
            ];
        })
        ->filter(fn ($col) => filled($col['name']))
        ->values()
        ->all();
    }

    /**
     * Cache payload helper
     */
    protected static function cacheExportPayload(string $prefix, array $payload, int $minutes = 5): string
    {
        $token = Str::random(32);
        Cache::put($prefix . $token, $payload, now()->addMinutes($minutes));
        return $token;
    }

    public static function getExportActions(): array
    {
        $resourceName = class_basename(static::class);
        $title = static::getModelLabel() ?? $resourceName;

        return [
            Action::make('exportExcel')
                ->label(__('exports.excel'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (HasTable $livewire) use ($resourceName) {
                    $query = static::getTableExportQuery($livewire);

                    $filename = $resourceName . '_' . now()->format('Y-m-d_His');
                    $columnData = static::buildColumnData($livewire);

                    $records = $query->limit(10000)->get();
                    static::eagerLoadRelationships($records, $columnData);

                    $token = static::cacheExportPayload('export_excel_', [
                        'records'  => $records,
                        'columns'  => $columnData,
                        'filename' => $filename,
                    ]);

                    return redirect()->route('filament.admin.exports.excel', ['token' => $token]);
                }),

            Action::make('exportPdf')
                ->label(__('exports.pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function (HasTable $livewire) use ($resourceName, $title) {
                    $query = static::getTableExportQuery($livewire);

                    $filename = $resourceName . '_' . now()->format('Y-m-d_His');
                    $columnData = static::buildColumnData($livewire);

                    $records = $query->limit(10000)->get();
                    static::eagerLoadRelationships($records, $columnData);

                    $token = static::cacheExportPayload('export_pdf_', [
                        'records'  => $records,
                        'columns'  => $columnData,
                        'filename' => $filename,
                        'title'    => $title,
                    ]);

                    return redirect()->route('filament.admin.exports.pdf', ['token' => $token]);
                }),

            Action::make('print')
                ->label(__('exports.print'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function (HasTable $livewire) use ($title) {
                    $query = static::getTableExportQuery($livewire);

                    $columnData = static::buildColumnData($livewire);

                    $records = $query->limit(10000)->get();
                    static::eagerLoadRelationships($records, $columnData);

                    $token = static::cacheExportPayload('export_print_', [
                        'records' => $records,
                        'columns' => $columnData,
                        'title'   => $title,
                    ]);

                    return redirect()->route('filament.admin.exports.print', ['token' => $token]);
                })
                ->openUrlInNewTab(),
        ];
    }
}
