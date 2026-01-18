<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Http\Requests\Api\V1\Student\StorePaymentRequest;
use App\Services\Student\PaymentService;
use Illuminate\Http\JsonResponse;

/**
 * @group Student Payments
 *
 * Student API for managing enrollment payments.
 */
class PaymentController extends ApiController
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Create Payment
     *
     * Record a payment for an enrollment.
     *
     * @urlParam enrollment integer required The ID of the enrollment. Example: 1
     * @bodyParam amount numeric required Payment amount (must be > 0). Example: 500.00
     * @bodyParam payment_method_id string required Payment method identifier. Example: cash
     * @bodyParam gateway_reference string optional Gateway reference if applicable. Example: TXN-12345
     * @bodyParam installment_id integer optional Installment ID if paying installment. Example: 1
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Payment created successfully.",
     *   "data": {
     *     "payment": {...},
     *     "enrollment": {...}
     *   }
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "The provided data is invalid.",
     *   "error": {
     *     "code": "VALIDATION_ERROR"
     *   }
     * }
     */
    public function store(StorePaymentRequest $request, int $enrollment): JsonResponse
    {
        $student = auth('students')->user();

        $payment = $this->paymentService->createPayment(
            $student,
            $enrollment,
            $request->validated()['amount'],
            $request->validated()['payment_method_id'],
            $request->validated()['gateway_reference'] ?? null,
            $request->validated()['installment_id'] ?? null
        );

        $enrollmentModel = $payment->enrollment;
        $paidAmount = $this->paymentService->getPaidAmount($enrollmentModel);
        $dueAmount = $this->paymentService->getDueAmount($enrollmentModel);
        $paymentStatus = $this->paymentService->getPaymentStatus($enrollmentModel);

        $enrollmentModel->setAttribute('paid_amount', $paidAmount);
        $enrollmentModel->setAttribute('due_amount', $dueAmount);
        $enrollmentModel->setAttribute('payment_status', $paymentStatus);

        return $this->createdResponse([
            'payment' => new \App\Http\Resources\V1\Student\PaymentResource($payment),
            'enrollment' => new \App\Http\Resources\V1\Student\EnrollmentResource($enrollmentModel),
        ], 'Payment created successfully.');
    }

    /**
     * List Payments
     *
     * Get payment history for an enrollment.
     *
     * @urlParam enrollment integer required The ID of the enrollment. Example: 1
     * @queryParam per_page integer optional Number of items per page. Default: 15. Example: 15
     * @queryParam page integer optional Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Payments retrieved successfully.",
     *   "data": [...],
     *   "meta": {
     *     "pagination": {...}
     *   }
     * }
     */
    public function index(int $enrollment): JsonResponse
    {
        $student = auth('students')->user();
        $perPage = (int) request()->input('per_page', 15);

        $payments = $this->paymentService->getPayments($student, $enrollment, $perPage);

        return $this->paginatedResponse(
            \App\Http\Resources\V1\Student\PaymentResource::collection($payments),
            'Payments retrieved successfully.'
        );
    }
}
