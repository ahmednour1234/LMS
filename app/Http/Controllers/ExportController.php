<?php

namespace App\Http\Controllers;

use App\Services\TableExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    protected TableExportService $exportService;

    public function __construct(TableExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Export Excel
     */
    public function excel(Request $request)
    {
        $token = $request->query('token');
        if (! $token) {
            abort(404);
        }

        $data = Cache::get("export_excel_{$token}");
        if (! $data) {
            abort(404);
        }

        Cache::forget("export_excel_{$token}");

        $records = $data['records'];
        $columns = $data['columns'];
        $filename = $data['filename'];

        return $this->exportService->exportXlsx($records, $columns, $filename);
    }

    /**
     * Export PDF
     */
    public function pdf(Request $request)
    {
        $token = $request->query('token');
        if (! $token) {
            abort(404);
        }

        $data = Cache::get("export_pdf_{$token}");
        if (! $data) {
            abort(404);
        }

        Cache::forget("export_pdf_{$token}");

        $records = $data['records'];
        $columns = $data['columns'];
        $filename = $data['filename'];
        $title = $data['title'] ?? null;

        return $this->exportService->exportPdf($records, $columns, $filename, $title);
    }

    /**
     * Print view
     */
    public function print(Request $request)
    {
        $token = $request->query('token');
        if (! $token) {
            abort(404);
        }

        $data = Cache::get("export_print_{$token}");
        if (! $data) {
            abort(404);
        }

        Cache::forget("export_print_{$token}");

        $records = $data['records'];
        $columns = $data['columns'];
        $title = $data['title'] ?? null;

        $view = $this->exportService->renderPrint($records, $columns, $title);
        $html = $view->render();

        return response($html . '<script>window.onload = function() { window.print(); }</script>')
            ->header('Content-Type', 'text/html');
    }
}

