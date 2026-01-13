<?php

namespace App\Filament\Admin\Resources\EnrollmentResource\Pages;

use App\Domain\Enrollment\Services\EnrollmentPriceCalculator;
use App\Enums\EnrollmentMode;
use App\Enums\EnrollmentStatus;
use App\Filament\Admin\Resources\EnrollmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateEnrollment extends CreateRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // enrolled_at default
        if (empty($data['enrolled_at'])) {
            $data['enrolled_at'] = now();
        }

        // set delivery_type based on course if missing
        if (!empty($data['course_id']) && empty($data['delivery_type'])) {
            $course = \App\Domain\Training\Models\Course::find($data['course_id']);
            if ($course) {
                $data['delivery_type'] = match ($course->delivery_type) {
                    \App\Domain\Training\Enums\DeliveryType::Onsite => 'onsite',
                    \App\Domain\Training\Enums\DeliveryType::Online => 'online',
                    \App\Domain\Training\Enums\DeliveryType::Hybrid => $data['delivery_type'] ?? 'online',
                    default => 'online',
                };
            }
        }

        // validate required
        if (empty($data['course_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'course_id' => 'Course is required.',
            ]);
        }

        if (empty($data['enrollment_mode'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'enrollment_mode' => 'Enrollment mode is required.',
            ]);
        }

        $deliveryType = $data['delivery_type'] ?? 'online';
        if (!in_array($deliveryType, ['onsite', 'online'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'delivery_type' => 'Delivery type must be either "onsite" or "online".',
            ]);
        }

        // branch rules
        if ($deliveryType === 'onsite') {
            if (empty($data['branch_id']) && !auth()->user()->isSuperAdmin()) {
                $data['branch_id'] = auth()->user()->branch_id;
            }

            if (empty($data['branch_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'branch_id' => 'Branch is required for onsite enrollment.',
                ]);
            }
        } else {
            $data['branch_id'] = null;
        }

        // Resolve course price
        $pricingService = app(\App\Services\PricingService::class);
        $branchId = $data['branch_id'] ?? null;

        $coursePrice = $pricingService->resolveCoursePrice(
            $data['course_id'],
            $branchId,
            $deliveryType
        );

        if (!$coursePrice) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'course_id' => 'No active course price found for this course/branch/delivery type combination.',
            ]);
        }

        // Validate mode allowed
        $calculator = app(EnrollmentPriceCalculator::class);
        $enrollmentMode = EnrollmentMode::from($data['enrollment_mode']);

        if (!$calculator->validateMode($coursePrice, $enrollmentMode)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'enrollment_mode' => 'This enrollment mode is not allowed for this course pricing configuration.',
            ]);
        }

        // sessions rules
        if ($enrollmentMode === EnrollmentMode::TRIAL) {
            $data['sessions_purchased'] = 1;
        } elseif ($enrollmentMode === EnrollmentMode::PER_SESSION) {
            $sessionsPurchased = (int) ($data['sessions_purchased'] ?? 0);
            $sessionsCount = $coursePrice->sessions_count ?? 1;

            if ($sessionsPurchased < 1 || $sessionsPurchased > $sessionsCount) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'sessions_purchased' => "Sessions purchased must be between 1 and {$sessionsCount}.",
                ]);
            }
        } elseif ($enrollmentMode === EnrollmentMode::COURSE_FULL) {
            $data['sessions_purchased'] = null;
        }

        // Calculate totals
        $priceResult = $calculator->calculate($coursePrice, $enrollmentMode, $data['sessions_purchased'] ?? null);
        $data['total_amount'] = $priceResult['total_amount'];
        $data['currency_code'] = $priceResult['currency_code'];

        // Set status
        $data['status'] = $enrollmentMode === EnrollmentMode::TRIAL
            ? EnrollmentStatus::ACTIVE->value
            : EnrollmentStatus::PENDING_PAYMENT->value;

        // Installment validation (optional)
        $pricingType = $data['pricing_type'] ?? 'full';
        if ($pricingType === 'installment' && $enrollmentMode === EnrollmentMode::COURSE_FULL) {
            if (!$coursePrice->allow_installments) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'pricing_type' => 'Installment pricing is not allowed for this course.',
                ]);
            }
        }

        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->refresh();

        DB::transaction(function () {
            // ✅ prevent duplicates
            $exists = \App\Domain\Accounting\Models\ArInvoice::where('enrollment_id', $this->record->id)->exists();
            if ($exists) {
                return;
            }

            // ✅ create AR invoice automatically
            \App\Domain\Accounting\Models\ArInvoice::create([
                'enrollment_id' => $this->record->id,
                'student_id'    => $this->record->student_id,
                'branch_id'     => $this->record->branch_id,
                'user_id'       => auth()->id(),
                'currency_code' => $this->record->currency_code,
                'total_amount'        => $this->record->total_amount,   // لو عندك اسمها total بدل amount عدلها
                'status'        => 'unpaid',                      // عدلها حسب enum عندك
                'issued_at'     => now(),
                'created_by'    => $this->record->created_by,
            ]);
        });

        // لو أنت محتاج event لباقي العمليات (اختياري)
        // event(new \App\Domain\Enrollment\Events\EnrollmentCreated($this->record));
    }
}
