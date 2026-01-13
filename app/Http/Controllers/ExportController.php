<?php

namespace App\Http\Controllers;

use App\Services\TableExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ExportController extends Controller
{
    public function __construct(
        protected TableExportService $exportService
    ) {}

    public function excel(Request $request)
    {
        $token = $request->query('token');
        if (!$token) abort(404);

        $data = Cache::get("export_excel_{$token}");
        if (!$data) abort(404);

        Cache::forget("export_excel_{$token}");

        return $this->exportService->exportXlsxFromCached(
            records: $data['records'],
            columns: collect($data['columns']), // array structure
            filename: $data['filename']
        );
    }

    public function pdf(Request $request)
    {
        $token = $request->query('token');
        if (!$token) abort(404);

        $data = Cache::get("export_pdf_{$token}");
        if (!$data) abort(404);

        Cache::forget("export_pdf_{$token}");

        return $this->exportService->exportPdfFromCached(
            records: $data['records'],
            columns: collect($data['columns']),
            filename: $data['filename'],
            title: $data['title'] ?? null,
        );
    }

    public function print(Request $request)
    {
        $token = $request->query('token');
        if (!$token) abort(404);

        $data = Cache::get("export_print_{$token}");
        if (!$data) abort(404);

        Cache::forget("export_print_{$token}");

        $view = $this->exportService->renderPrintFromCached(
            records: $data['records'],
            columns: collect($data['columns']),
            title: $data['title'] ?? null,
        );

        return response($view->render() . '<script>window.onload=function(){window.print();}</script>')
            ->header('Content-Type', 'text/html');
    }
}
