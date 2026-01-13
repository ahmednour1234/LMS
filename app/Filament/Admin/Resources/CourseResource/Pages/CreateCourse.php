<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\CoursePrice;
use App\Filament\Admin\Resources\CourseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['name'] = [
            'ar' => $data['name']['ar'] ?? '',
            'en' => $data['name']['en'] ?? '',
        ];

        $data['description'] = [
            'ar' => $data['description']['ar'] ?? null,
            'en' => $data['description']['en'] ?? null,
        ];

        return $data;
    }

    protected function afterCreate(): void
    {
        $course = $this->record;
        $data = $this->data;

        if (isset($data['online_pricing']) && $this->hasPricingData($data['online_pricing'])) {
            $this->createCoursePrice($course->id, DeliveryType::Online, $data['online_pricing']);
        }

        if (isset($data['hybrid_pricing']) && $this->hasPricingData($data['hybrid_pricing'])) {
            $this->createCoursePrice($course->id, DeliveryType::Hybrid, $data['hybrid_pricing']);
        }
    }

    protected function hasPricingData(array $pricingData): bool
    {
        $pricingMode = $pricingData['pricing_mode'] ?? 'course_total';
        
        if (in_array($pricingMode, ['course_total', 'both'])) {
            if (!empty($pricingData['price']) && (float) $pricingData['price'] > 0) {
                return true;
            }
        }
        
        if (in_array($pricingMode, ['per_session', 'both'])) {
            if (!empty($pricingData['session_price']) && (float) $pricingData['session_price'] > 0) {
                return true;
            }
        }
        
        return false;
    }

    protected function createCoursePrice(int $courseId, DeliveryType $deliveryType, array $pricingData): void
    {
        CoursePrice::create([
            'course_id' => $courseId,
            'branch_id' => null,
            'delivery_type' => $deliveryType,
            'pricing_mode' => $pricingData['pricing_mode'] ?? 'course_total',
            'price' => $pricingData['price'] ?? null,
            'session_price' => $pricingData['session_price'] ?? null,
            'sessions_count' => $pricingData['sessions_count'] ?? null,
            'allow_installments' => $pricingData['allow_installments'] ?? false,
            'min_down_payment' => $pricingData['min_down_payment'] ?? null,
            'max_installments' => $pricingData['max_installments'] ?? null,
            'is_active' => $pricingData['is_active'] ?? true,
        ]);
    }
}
