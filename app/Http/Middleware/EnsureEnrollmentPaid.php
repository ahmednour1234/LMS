<?php

namespace App\Http\Middleware;

use App\Domain\Accounting\Models\Payment;
use App\Domain\Enrollment\Models\Enrollment;
use App\Http\Enums\ApiErrorCode;
use App\Http\Services\ApiResponseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnrollmentPaid
{
    public function handle(Request $request, Closure $next): Response
    {
        $enrollment = $request->attributes->get('enrollment');
        $course = $request->attributes->get('course');

        if (!$enrollment) {
            return ApiResponseService::error(
                ApiErrorCode::FORBIDDEN,
                'Enrollment not found.',
                null,
                403
            );
        }

        $paidAmount = Payment::where('enrollment_id', $enrollment->id)
            ->where('status', 'completed')
            ->sum('amount');

        $dueAmount = max(0, ($enrollment->total_amount ?? 0) - $paidAmount);
        $isPaid = $dueAmount <= 0.01;

        $allowUnpaidAccess = $course->allow_unpaid_access ?? false;

        if (!$isPaid && !$allowUnpaidAccess) {
            return ApiResponseService::error(
                ApiErrorCode::FORBIDDEN,
                'Payment required to access this content.',
                [
                    'paid_amount' => (float) $paidAmount,
                    'due_amount' => (float) $dueAmount,
                    'total_amount' => (float) ($enrollment->total_amount ?? 0),
                ],
                403
            );
        }

        $request->attributes->set('paid_amount', $paidAmount);
        $request->attributes->set('due_amount', $dueAmount);
        $request->attributes->set('is_paid', $isPaid);

        return $next($request);
    }
}
