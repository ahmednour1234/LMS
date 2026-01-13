<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TableExportService
{
    public function __construct(
        protected PdfService $pdfService
    ) {}

    /**
     * Normalize column label (avoid N/A duplicates)
     */
    protected function normalizeLabel(string $label, int $index): string
    {
        $label = trim($label);
        return $label !== '' ? $label : ('Column ' . ($index + 1));
    }

    /**
     * Get record value by column "name" (supports relations: course.code)
     */
    protected function getValueFromRecord($record, string $name)
    {
        return data_get($record, $name);
    }

    /**
     * Format values for export (dates/arrays/bool/objects)
     */
    protected function formatValue($value): mixed
    {
        if ($value === null) return '';

        if (is_array($value)) {
            $locale = app()->getLocale();
            return $value[$locale] ?? $value['ar'] ?? $value['en'] ?? json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        if (is_object($value) && !($value instanceof \DateTimeInterface)) {
            return method_exists($value, '__toString')
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $value;
    }

    /**
     * ✅ Export Excel from cached structure:
     * columns: [{name: "course.code", label: "Course"}, ...]
     */
    public function exportXlsxFromCached(Builder|Collection $records, Collection $columns, string $filename): BinaryFileResponse
    {
        $records = $records instanceof Collection ? $records : $records->get();

        if ($columns->isEmpty()) {
            abort(422, 'No columns found for export.');
        }

        $exportData = $records->map(function ($record) use ($columns) {
            $row = [];
            $usedHeaders = [];

            foreach ($columns->values() as $i => $col) {
                $name  = $col['name']  ?? '';
                $label = $col['label'] ?? $name;

                $header = $this->normalizeLabel((string) $label, $i);

                // avoid duplicate headers
                $base = $header;
                $n = 2;
                while (isset($usedHeaders[$header])) {
                    $header = $base . " ($n)";
                    $n++;
                }
                $usedHeaders[$header] = true;

                $value = $name ? $this->getValueFromRecord($record, $name) : '';
                $value = $this->formatValue($value);

                if (is_string($value)) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                }

                $row[$header] = $value ?? '';
            }

            return $row;
        });

        $dir = storage_path('app/temp');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $filePath = $dir . '/' . $filename . '.xlsx';
        (new FastExcel($exportData))->export($filePath);

        return response()
            ->download($filePath, $filename . '.xlsx')
            ->deleteFileAfterSend(true);
    }

    /**
     * ✅ Export PDF from cached structure
     */
    public function exportPdfFromCached(Builder|Collection $records, Collection $columns, string $filename, ?string $title = null): Response
    {
        $records = $records instanceof Collection ? $records : $records->get();
        $locale = app()->getLocale();

        $html = view('exports.pdf-table', [
            'records' => $records,
            'columns' => $columns, // array columns
            'title'   => $title ?? $filename,
            'locale'  => $locale,
            'isRtl'   => $locale === 'ar',
        ])->render();

        $response = $this->pdfService->renderFromHtml($html, [
            'format' => 'A4-L',
        ]);

        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"');

        return $response;
    }

    /**
     * ✅ Print from cached structure
     */
    public function renderPrintFromCached(Builder|Collection $records, Collection $columns, ?string $title = null)
    {
        $records = $records instanceof Collection ? $records : $records->get();
        $locale = app()->getLocale();

        return view('exports.print-table', [
            'records' => $records,
            'columns' => $columns,
            'title'   => $title,
            'locale'  => $locale,
            'isRtl'   => $locale === 'ar',
        ]);
    }
}
