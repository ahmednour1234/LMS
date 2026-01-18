<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Http\Requests\Api\V1\Student\StoreEnrollmentRequest;
use App\Services\Student\EnrollmentService;
use App\Services\Student\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Student Enrollments
 *
 * Student API for managing course enrollments.
 */
class EnrollmentController extends ApiController
{
    public function __construct(
        private EnrollmentService $enrollmentService,
        private PaymentService $paymentService
    ) {}

    /**
     * Create Enrollment
     *
     * Enroll in a course. Returns existing enrollment if already enrolled.
     *
     * @bodyParam course_id integer required The ID of the course. Example: 1
     * @bodyParam delivery_type string required Delivery type: online, onsite, hybrid. Example: online
     * @bodyParam branch_id integer required_if:delivery_type,onsite Branch ID for onsite courses. Example: 1
     * @bodyParam pricing_mode string optional Pricing mode: course_total, per_session. Default: course_total. Example: course_total
     * @bodyParam selected_price_option_id integer optional Selected price option ID. Example: 1
     * @bodyParam sessions_purchased integer optional Number of sessions (required for per_session). Example: 5
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Enrollment created successfully.",
     *   "data": {
     *     "id": 1,
     *     "reference": "ENR-2026-000001",
     *     "status": "pending_payment",
     *     "total_amount": 1000.00
     *   }
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "The provided data is invalid.",
     *   "error": {
     *     "code": "VALIDATION_ERROR",
     *     "details": {...}
     *   }
     * }
     */
    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $student = auth('students')->user();

        $enrollment = $this->enrollmentService->createEnrollment(
            $student,
            $request->validated()['course_id'],
            $request->validated()['delivery_type'],
            $request->validated()['branch_id'] ?? null,
            $request->validated()['pricing_mode'] ?? 'course_total',
            $request->validated()['selected_price_option_id'] ?? null,
            $request->validated()['sessions_purchased'] ?? null
        );

        $paidAmount = $this->paymentService->getPaidAmount($enrollment);
        $dueAmount = $this->paymentService->getDueAmount($enrollment);
        $paymentStatus = $this->paymentService->getPaymentStatus($enrollment);

        $enrollment->setAttribute('paid_amount', $paidAmount);
        $enrollment->setAttribute('due_amount', $dueAmount);
        $enrollment->setAttribute('payment_status', $paymentStatus);

        return $this->createdResponse(
            new \App\Http\Resources\V1\Student\EnrollmentResource($enrollment),
            'Enrollment created successfully.'
        );
    }

    /**
     * List Enrollments
     *
     * Get a paginated list of student's enrollments with filters.
     *
     * @queryParam status string optional Filter by status: pending, pending_payment, active, completed, cancelled. Example: active
     * @queryParam course_id integer optional Filter by course ID. Example: 1
     * @queryParam payment_status string optional Filter by payment status: paid, partial, unpaid. Example: paid
     * @queryParam from date optional Filter from date (YYYY-MM-DD). Example: 2026-01-01
     * @queryParam to date optional Filter to date (YYYY-MM-DD). Example: 2026-12-31
     * @queryParam per_page integer optional Number of items per page. Default: 15. Example: 15
     * @queryParam page integer optional Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Enrollments retrieved successfully.",
     *   "data": [...],
     *   "meta": {
     *     "pagination": {...}
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $student = auth('students')->user();

        $filters = [
            'status' => $request->input('status'),
            'course_id' => $request->input('course_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'payment_status' => $request->input('payment_status'),
        ];

        $perPage = (int) $request->input('per_page', 15);
        $enrollments = $this->enrollmentService->getStudentEnrollments($student, $filters, $perPage);

        $enrollments->getCollection()->transform(function ($enrollment) {
            $paidAmount = (float) $enrollment->payments->sum('amount');
            $dueAmount = max(0, ($enrollment->total_amount ?? 0) - $paidAmount);
            $paymentStatus = $dueAmount <= 0.01 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            $enrollment->setAttribute('paid_amount', $paidAmount);
            $enrollment->setAttribute('due_amount', $dueAmount);
            $enrollment->setAttribute('payment_status', $paymentStatus);

            return $enrollment;
        });

        return $this->paginatedResponse(
            \App\Http\Resources\V1\Student\EnrollmentResource::collection($enrollments),
            'Enrollments retrieved successfully.'
        );
    }

    /**
     * Show Enrollment
     *
     * Get enrollment details with payment summary.
     *
     * @urlParam enrollment integer required The ID of the enrollment. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Enrollment retrieved successfully.",
     *   "data": {
     *     "id": 1,
     *     "reference": "ENR-2026-000001",
     *     "status": "active",
     *     "total_amount": 1000.00,
     *     "paid_amount": 500.00,
     *     "due_amount": 500.00
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Enrollment not found.",
     *   "error": {
     *     "code": "NOT_FOUND"
     *   }
     * }
     */
    public function show(int $enrollment): JsonResponse
    {
        $student = auth('students')->user();
        $enrollmentModel = $this->enrollmentService->getEnrollment($student, $enrollment);

        if (!$enrollmentModel) {
            return $this->errorResponse(
                ApiErrorCode::NOT_FOUND,
                'Enrollment not found.',
                null,
                404
            );
        }

        $paidAmount = $this->paymentService->getPaidAmount($enrollmentModel);
        $dueAmount = $this->paymentService->getDueAmount($enrollmentModel);
        $paymentStatus = $this->paymentService->getPaymentStatus($enrollmentModel);

        $enrollmentModel->setAttribute('paid_amount', $paidAmount);
        $enrollmentModel->setAttribute('due_amount', $dueAmount);
        $enrollmentModel->setAttribute('payment_status', $paymentStatus);

        return $this->successResponse(
            new \App\Http\Resources\V1\Student\EnrollmentResource($enrollmentModel),
            'Enrollment retrieved successfully.'
        );
    }
}
