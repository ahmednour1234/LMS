<?php

namespace App\Services;

use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\File;

class TableExportService
{
    public function __construct(
        protected PdfService $pdfService
    ) {}

    /**
     * Build query from Filament table state (filters, search, sort)
     */
    public function buildQueryFromTableState(HasTable $livewireComponent): Builder
    {
        // Filament v3: includes filters/search/sort
        return $livewireComponent->getFilteredTableQuery();
    }

    /**
     * Get visible columns from table (skip hidden / unnamed columns)
     */
    public function getVisibleColumns(HasTable $livewireComponent): Collection
    {
        $table = $livewireComponent->getTable();

        return collect($table->getColumns())
            ->filter(fn ($column) => method_exists($column, 'isHidden') ? ! $column->isHidden() : true)
            ->filter(fn ($column) => method_exists($column, 'getName') && filled($column->getName()));
    }

    /**
     * ✅ Get raw value for a given table column from record.
     * Uses Filament column state so it works with relations + formatStateUsing + accessors.
     */
    protected function getColumnState($column, $record)
    {
        // Preferred: Filament column state
        if (method_exists($column, 'getState')) {
            return $column->getState($record);
        }

        // Fallback: try name via data_get
        $name = method_exists($column, 'getName') ? $column->getName() : null;
        return $name ? data_get($record, $name) : null;
    }

    /**
     * Get column label safely
     */
    protected function getColumnLabel($column): string
    {
        $name = method_exists($column, 'getName') ? (string) $column->getName() : '';

        if (method_exists($column, 'getLabel')) {
            $label = $column->getLabel();
            if (is_string($label) && $label !== '') {
                return $label;
            }
        }

        return $name ?: 'N/A';
    }

    /**
     * Format value for export
     */
    protected function formatColumnValue($value, $column)
    {
        if ($value === null) {
            return '';
        }

        // Arrays (e.g. multilingual JSON)
        if (is_array($value)) {
            $locale = app()->getLocale();
            return $value[$locale] ?? $value['ar'] ?? $value['en'] ?? json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        // Dates
        if ($value instanceof \DateTimeInterface) {
            // if column has dateTime() it may still pass DateTime
            return $value->format('Y-m-d H:i:s');
        }

        // Money columns (Filament has getCurrency in some column types)
        if (method_exists($column, 'getCurrency')) {
            $currency = $column->getCurrency();
            if ($currency) {
                $locale = app()->getLocale();
                $formatter = new \NumberFormatter($locale . '@currency=' . $currency, \NumberFormatter::CURRENCY);
                return $formatter->formatCurrency((float) $value, $currency);
            }
        }

        // Boolean
        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        // Objects
        if (is_object($value) && !($value instanceof \DateTimeInterface)) {
            return method_exists($value, '__toString')
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $value;
    }

    /**
     * Export to Excel (XLSX)
     */
    public function exportXlsx(Builder|Collection $query, Collection $columns, string $filename): BinaryFileResponse
    {
        $records = $query instanceof Collection ? $query : $query->get();

        // If columns were not passed correctly, fail loudly (better than empty file)
        if ($columns->isEmpty()) {
            abort(422, 'No visible columns found for export.');
        }

        $exportData = $records->map(function ($record) use ($columns) {
            $row = [];

            foreach ($columns as $column) {
                $label = $this->getColumnLabel($column);

                // ✅ get value using Filament state (fix empty excel issue)
                $value = $this->getColumnState($column, $record);

                $value = $this->formatColumnValue($value, $column);

                if (is_string($value)) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                }

                $row[$label] = $value ?? '';
            }

            return $row;
        });

        $dir = storage_path('app/temp');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $filePath = $dir . '/' . $filename . '.xlsx';

        (new FastExcel($exportData))->export($filePath);

        return response()
            ->download($filePath, $filename . '.xlsx')
            ->deleteFileAfterSend(true);
    }

    /**
     * Export to PDF
     */
    public function exportPdf(Builder|Collection $query, Collection $columns, string $filename, ?string $title = null): Response
    {
        $records = $query instanceof Collection ? $query : $query->get();
        $locale = app()->getLocale();

        $html = view('exports.pdf-table', [
            'records' => $records,
            'columns' => $columns,
            'title' => $title ?? $filename,
            'locale' => $locale,
            'isRtl' => $locale === 'ar',
        ])->render();

        $response = $this->pdfService->renderFromHtml($html, [
            'format' => 'A4-L',
        ]);

        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"');

        return $response;
    }

    /**
     * Render print view
     */
    public function renderPrint(Builder|Collection $query, Collection $columns, ?string $title = null)
    {
        $records = $query instanceof Collection ? $query : $query->get();
        $locale = app()->getLocale();

        return view('exports.print-table', [
            'records' => $records,
            'columns' => $columns,
            'title' => $title,
            'locale' => $locale,
            'isRtl' => $locale === 'ar',
        ]);
    }
}
