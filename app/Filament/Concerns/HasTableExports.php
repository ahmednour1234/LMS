<?php

namespace App\Filament\Concerns;

use App\Services\TableExportService;
use Filament\Tables\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait HasTableExports
{
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
            ->map(function ($name) {
                if (str_contains($name, '.')) {
                    return explode('.', $name)[0];
                }
                return null;
            })
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

        return $label !== '' ? $label : $fallback;
    }

    /**
     * Serialize Filament columns into {name,label} only (cache friendly)
     */
    protected static function buildColumnData(HasTable $livewire): array
    {
        /** @var TableExportService $service */
        $service = app(TableExportService::class);

        // ✅ Use service so we only get visible + named columns
        $columns = $service->getVisibleColumns($livewire);

        return $columns->values()->map(function ($column) {
            $name = method_exists($column, 'getName') ? (string) $column->getName() : '';
            $rawLabel = method_exists($column, 'getLabel') ? $column->getLabel() : '';

            $label = static::stringifyLabel($rawLabel, $name ?: 'Column');

            return [
                'name'  => $name,
                'label' => $label,
            ];
        })
        // ✅ drop columns with empty name (important)
        ->filter(fn ($col) => filled($col['name']))
        ->values()
        ->all();
    }

    /**
     * Helper: prepare records + columns and put in cache
     */
    protected static function cacheExportPayload(
        string $cacheKey,
        HasTable $livewire,
        array $payload,
        int $minutes = 5
    ): string {
        $token = Str::random(32);

        Cache::put($cacheKey . $token, $payload, now()->addMinutes($minutes));

        return $token;
    }

    /**
     * Get export actions (Excel, PDF, Print)
     * Use in table():
     * ->headerActions(array_merge([...], static::getExportActions()))
     */
    public static function getExportActions(): array
    {
        $resourceName = class_basename(static::class);
        $title = static::getModelLabel() ?? $resourceName;

        return [
            // =========================
            // Excel
            // =========================
            Action::make('exportExcel')
                ->label(__('exports.excel'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (HasTable $livewire) use ($resourceName) {
                    /** @var TableExportService $service */
                    $service = app(TableExportService::class);

                    $query = $service->buildQueryFromTableState($livewire);
                    $filename = $resourceName . '_' . now()->format('Y-m-d_His');

                    $columnData = static::buildColumnData($livewire);

                    // Records
                    $records = $query->limit(10000)->get();
                    static::eagerLoadRelationships($records, $columnData);

                    $token = static::cacheExportPayload(
                        cacheKey: 'export_excel_',
                        livewire: $livewire,
                        payload: [
                            'records'  => $records,
                            'columns'  => $columnData,
                            'filename' => $filename,
                        ],
                        minutes: 5
                    );

                    return redirect()->route('filament.admin.exports.excel', ['token' => $token]);
                }),

            // =========================
            // PDF
            // =========================
            Action::make('exportPdf')
                ->label(__('exports.pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function (HasTable $livewire) use ($resourceName, $title) {
                    /** @var TableExportService $service */
                    $service = app(TableExportService::class);

                    $query = $service->buildQueryFromTableState($livewire);
                    $filename = $resourceName . '_' . now()->format('Y-m-d_His');

                    $columnData = static::buildColumnData($livewire);

                    $records = $query->limit(10000)->get();
                    static::eagerLoadRelationships($records, $columnData);

                    $token = static::cacheExportPayload(
                        cacheKey: 'export_pdf_',
                        livewire: $livewire,
                        payload: [
                            'records'  => $records,
                            'columns'  => $columnData,
                            'filename' => $filename,
                            'title'    => $title,
                        ],
                        minutes: 5
                    );

                    return redirect()->route('filament.admin.exports.pdf', ['token' => $token]);
                }),

            // =========================
            // Print
            // =========================
            Action::make('print')
                ->label(__('exports.print'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function (HasTable $livewire) use ($title) {
                    /** @var TableExportService $service */
                    $service = app(TableExportService::class);

                    $query = $service->buildQueryFromTableState($livewire);
                    $columnData = static::buildColumnData($livewire);

                    $records = $query->limit(10000)->get();
                    static::eagerLoadRelationships($records, $columnData);

                    $token = static::cacheExportPayload(
                        cacheKey: 'export_print_',
                        livewire: $livewire,
                        payload: [
                            'records' => $records,
                            'columns' => $columnData,
                            'title'   => $title,
                        ],
                        minutes: 5
                    );

                    return redirect()->route('filament.admin.exports.print', ['token' => $token]);
                })
                ->openUrlInNewTab(),
        ];
    }
}
