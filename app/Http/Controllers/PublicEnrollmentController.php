<?php

namespace App\Http\Controllers;

use App\Domain\Enrollment\Models\Enrollment;
use App\Services\QrCodeService;
use Illuminate\Http\Request;

class PublicEnrollmentController extends Controller
{
    protected QrCodeService $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Show public enrollment view (for QR code link)
     */
    public function show(string $reference)
    {
        $enrollment = Enrollment::where('reference', $reference)
            ->with(['student', 'course', 'branch', 'arInvoice.arInstallments', 'payments'])
            ->firstOrFail();

        $publicUrl = route('public.enrollment.show', ['reference' => $enrollment->reference]);
        $qrCodeSvg = $this->qrCodeService->generateSvg($publicUrl);

        return view('public.enrollment', [
            'enrollment' => $enrollment,
            'qrCodeSvg' => $qrCodeSvg,
        ]);
    }
}

