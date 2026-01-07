<?php

namespace App\Http\Controllers;

use App\Domain\Enrollment\Models\Enrollment;
use App\Services\PdfService;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    protected PdfService $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Print enrollment
     */
    public function print(Enrollment $enrollment)
    {
        $enrollment->load(['student', 'course', 'branch', 'arInvoice.arInstallments', 'payments']);
        
        $appName = \App\Models\Setting::where('key', 'app_name')->first()?->value ?? [];
        $appNameText = is_array($appName) ? ($appName[app()->getLocale()] ?? $appName['en'] ?? 'LMS') : (string) $appName;
        
        return $this->pdfService->render('pdf.enrollment', [
            'enrollment' => $enrollment,
            'app_name' => $appNameText,
        ]);
    }
}

