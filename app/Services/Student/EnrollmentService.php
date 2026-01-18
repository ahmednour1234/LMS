<?php

namespace App\Services\Student;

use App\Domain\Branch\Models\Branch;
use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Enrollment\Models\Student;
use App\Domain\Enrollment\Services\EnrollmentPriceCalculator;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CoursePrice;
use App\Domain\Training\Enums\DeliveryType;
use App\Enums\EnrollmentMode;
use App\Enums\EnrollmentStatus;
use App\Exceptions\BusinessException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class EnrollmentService
{
    public function __construct(
        private EnrollmentPriceCalculator $priceCalculator
    ) {}

    public function createEnrollment(
        Student $student,
        int $courseId,
        string $deliveryType,
        ?int $branchId = null,
        string $pricingMode = 'course_total',
        ?int $selectedPriceOptionId = null,
        ?int $sessionsPurchased = null
    ): Enrollment {
        $course = Course::findOrFail($courseId);

        if (!$course->is_active) {
            throw new BusinessException('Course is not active.');
        }

        $deliveryTypeEnum = DeliveryType::from($deliveryType);
        if ($course->delivery_type && $course->delivery_type !== $deliveryTypeEnum) {
            throw new BusinessException('Delivery type not allowed for this course.');
        }

        if ($deliveryTypeEnum === DeliveryType::ONSITE && !$branchId) {
            throw new BusinessException('Branch is required for onsite courses.');
        }

        if ($branchId) {
            $branch = Branch::findOrFail($branchId);
        }

        $existingEnrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->where('delivery_type', $deliveryType)
            ->whereIn('status', ['pending', 'pending_payment', 'active'])
            ->first();

        if ($existingEnrollment) {
            return $existingEnrollment;
        }

        $coursePrice = $this->resolveCoursePrice($course, $branchId, $deliveryType, $selectedPriceOptionId);

        $enrollmentMode = $this->determineEnrollmentMode($pricingMode, $sessionsPurchased);

        if (!$this->priceCalculator->validateMode($coursePrice, $enrollmentMode)) {
            throw new BusinessException('Enrollment mode not allowed for this pricing option.');
        }

        $priceData = $this->priceCalculator->calculate(
            $coursePrice,
            $enrollmentMode,
            $sessionsPurchased
        );

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $courseId,
            'delivery_type' => $deliveryType,
            'branch_id' => $branchId,
            'enrollment_mode' => $enrollmentMode,
            'pricing_type' => $pricingMode,
            'sessions_purchased' => $sessionsPurchased,
            'total_amount' => $priceData['total_amount'],
            'currency_code' => $priceData['currency_code'],
            'status' => EnrollmentStatus::PENDING_PAYMENT,
            'registered_at' => now(),
        ]);

        return $enrollment;
    }

    public function getStudentEnrollments(
        Student $student,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Enrollment::where('student_id', $student->id)
            ->with(['course.program', 'branch', 'payments' => function ($q) {
                $q->where('status', 'completed');
            }]);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (isset($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $paymentStatus = $filters['payment_status'] ?? null;
        if ($paymentStatus) {
            $query->whereHas('payments', function ($q) use ($paymentStatus) {
                if ($paymentStatus === 'paid') {
                    $q->where('status', 'completed')
                        ->selectRaw('enrollment_id, SUM(amount) as total_paid')
                        ->groupBy('enrollment_id')
                        ->havingRaw('SUM(amount) >= enrollments.total_amount');
                } elseif ($paymentStatus === 'partial') {
                    $q->where('status', 'completed')
                        ->selectRaw('enrollment_id, SUM(amount) as total_paid')
                        ->groupBy('enrollment_id')
                        ->havingRaw('SUM(amount) > 0 AND SUM(amount) < enrollments.total_amount');
                } elseif ($paymentStatus === 'unpaid') {
                    $q->where('status', 'completed')
                        ->selectRaw('enrollment_id, SUM(amount) as total_paid')
                        ->groupBy('enrollment_id')
                        ->havingRaw('SUM(amount) = 0 OR SUM(amount) IS NULL');
                }
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getEnrollment(Student $student, int $enrollmentId): ?Enrollment
    {
        return Enrollment::where('student_id', $student->id)
            ->where('id', $enrollmentId)
            ->with(['course.program', 'branch', 'payments'])
            ->first();
    }

    private function resolveCoursePrice(
        Course $course,
        ?int $branchId,
        string $deliveryType,
        ?int $selectedPriceOptionId
    ): CoursePrice {
        if ($selectedPriceOptionId) {
            $price = CoursePrice::where('id', $selectedPriceOptionId)
                ->where('course_id', $course->id)
                ->where('is_active', true)
                ->first();

            if (!$price) {
                throw new BusinessException('Selected price option not found or inactive.');
            }

            return $price;
        }

        $query = CoursePrice::where('course_id', $course->id)
            ->where('is_active', true)
            ->where('delivery_type', $deliveryType);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })->orderByRaw('CASE WHEN branch_id = ? THEN 0 ELSE 1 END', [$branchId]);
        } else {
            $query->whereNull('branch_id');
        }

        $price = $query->first();

        if (!$price) {
            throw new BusinessException('No active price found for this course configuration.');
        }

        return $price;
    }

    private function determineEnrollmentMode(string $pricingMode, ?int $sessionsPurchased): EnrollmentMode
    {
        return match ($pricingMode) {
            'course_total' => EnrollmentMode::COURSE_FULL,
            'per_session' => $sessionsPurchased && $sessionsPurchased === 1
                ? EnrollmentMode::TRIAL
                : EnrollmentMode::PER_SESSION,
            default => EnrollmentMode::COURSE_FULL,
        };
    }
}
