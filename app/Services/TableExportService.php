<?php

namespace App\Services;

use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TableExportService
{
    protected PdfService $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Build query from Filament table state (filters, search, sort)
     *
     * @param HasTable $livewireComponent
     * @return Builder
     */
    public function buildQueryFromTableState(HasTable $livewireComponent): Builder
    {
        // In Filament v3, getFilteredTableQuery() is available on the HasTable contract
        // which ListRecords implements. This method already includes filters, search, and sort.
        return $livewireComponent->getFilteredTableQuery();
    }

    /**
     * Get visible columns from table
     *
     * @param HasTable $livewireComponent
     * @return Collection
     */
    protected function getVisibleColumns(HasTable $livewireComponent): Collection
    {
        $table = $livewireComponent->getTable();
        $columns = $table->getColumns();

        // Filter only visible columns
        return collect($columns)->filter(function ($column) {
            return ! $column->isHidden();
        });
    }

    /**
     * Get value from record by column name (handles relationships)
     *
     * @param mixed $record
     * @param string $name
     * @return mixed
     */
    protected function getRecordValue($record, string $name)
    {
        // Handle relationship columns (e.g., 'branch.name')
        if (str_contains($name, '.')) {
            $parts = explode('.', $name);
            $value = $record;
            foreach ($parts as $part) {
                if ($value === null) {
                    return null;
                }
                $value = is_object($value) ? $value->getAttribute($part) : ($value[$part] ?? null);
            }
            return $value;
        }

        return $record->getAttribute($name);
    }

    /**
     * Format column value based on column type
     *
     * @param mixed $value
     * @param mixed $column
     * @return mixed
     */
    protected function formatColumnValue($value, $column)
    {
        if ($value === null) {
            return '';
        }

        // Handle date columns
        if (method_exists($column, 'getFormat') && $column->getFormat()) {
            $format = $column->getFormat();
            if (in_array($format, ['date', 'dateTime', 'time'])) {
                return $value instanceof \DateTimeInterface
                    ? $value->format($format === 'date' ? 'Y-m-d' : ($format === 'time' ? 'H:i:s' : 'Y-m-d H:i:s'))
                    : $value;
            }
        }

        // Handle money columns
        if (method_exists($column, 'getCurrency') && $column->getCurrency()) {
            $currency = $column->getCurrency();
            $locale = app()->getLocale();
            $formatter = new \NumberFormatter($locale . '@currency=' . $currency, \NumberFormatter::CURRENCY);
            return $formatter->formatCurrency($value, $currency);
        }

        // Handle boolean columns
        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        return $value;
    }

    /**
     * Export query results to Excel (XLSX)
     *
     * @param Builder $query
     * @param Collection $columns
     * @param string $filename
     * @return BinaryFileResponse
     */
    public function exportXlsx(Builder $query, Collection $columns, string $filename): BinaryFileResponse
    {
        $records = $query->get();
        $locale = app()->getLocale();

        // Prepare data for export
        $exportData = $records->map(function ($record) use ($columns) {
            $row = [];
            foreach ($columns as $column) {
                // Get column name and label - handle both method calls and property access
                $name = is_callable([$column, 'getName']) ? $column->getName() : ($column->getName ?? '');
                $label = is_callable([$column, 'getLabel']) ? $column->getLabel() : ($column->getLabel ?? $name);

                // Get value from record
                $value = $this->getRecordValue($record, $name);

                // Format the value
                $value = $this->formatColumnValue($value, $column);

                // Use translated label as key
                $row[$label] = $value ?? '';
            }
            return $row;
        });

        // Generate file path
        $filePath = storage_path('app/temp/' . $filename . '.xlsx');
        $directory = dirname($filePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Export using FastExcel
        (new FastExcel($exportData))->export($filePath);

        return response()->download($filePath, $filename . '.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Export query results to PDF
     *
     * @param Builder $query
     * @param Collection $columns
     * @param string $filename
     * @param string|null $title
     * @return Response
     */
    public function exportPdf(Builder $query, Collection $columns, string $filename, ?string $title = null): Response
    {
        $records = $query->get();
        $locale = app()->getLocale();
        $isRtl = $locale === 'ar';

        // Prepare data for PDF
        $data = [
            'records' => $records,
            'columns' => $columns,
            'title' => $title ?? $filename,
            'locale' => $locale,
            'isRtl' => $isRtl,
        ];

        // Render PDF using PdfService
        $html = view('exports.pdf-table', $data)->render();

        $response = $this->pdfService->renderFromHtml($html, [
            'format' => 'A4-L', // Landscape for tables
        ]);

        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"');

        return $response;
    }

    /**
     * Render print view
     *
     * @param Builder $query
     * @param Collection $columns
     * @param string|null $title
     * @return \Illuminate\Contracts\View\View
     */
    public function renderPrint(Builder $query, Collection $columns, ?string $title = null)
    {
        $records = $query->get();
        $locale = app()->getLocale();
        $isRtl = $locale === 'ar';

        return view('exports.print-table', [
            'records' => $records,
            'columns' => $columns,
            'title' => $title,
            'locale' => $locale,
            'isRtl' => $isRtl,
        ]);
    }
}

