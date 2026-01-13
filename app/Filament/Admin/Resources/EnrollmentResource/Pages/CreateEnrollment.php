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
        if (empty($data['enrolled_at'])) {
            $data['enrolled_at'] = now();
        }

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

        $data['total_amount']  = (float) $priceResult['total_amount'];
        $data['currency_code'] = $priceResult['currency_code'];

        // status
        $data['status'] = $enrollmentMode === EnrollmentMode::TRIAL
            ? EnrollmentStatus::ACTIVE->value
            : EnrollmentStatus::PENDING_PAYMENT->value;

        // Installment validation
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
            $exists = \App\Domain\Accounting\Models\ArInvoice::where('enrollment_id', $this->record->id)->exists();
            if ($exists) {
                return;
            }

            $total = (float) ($this->record->total_amount ?? 0);

            // ✅ branch fallback (لأن SQL كان بيطلع ?)
            $branchId = $this->record->branch_id ?? auth()->user()->branch_id;

            // ✅ forceFill لتخطي fillable (حل نهائي لمشكلة due_amount)
            $invoice = new \App\Domain\Accounting\Models\ArInvoice();

            $invoice->forceFill([
                'enrollment_id' => $this->record->id,
                'branch_id'     => $branchId,
                'user_id'       => auth()->id(),
                'currency_code' => $this->record->currency_code ?? null,

                'total_amount'  => $total,
                'due_amount'    => $total,

                'status'        => 'unpaid',
                'issued_at'     => now(),
                'created_by'    => auth()->id(),
            ])->save();
        });
    }
}
