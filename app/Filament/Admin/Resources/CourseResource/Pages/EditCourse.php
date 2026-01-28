<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\CoursePrice;
use App\Filament\Admin\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function afterSave(): void
    {
        $course = $this->record;
        $data = $this->data;

        if (isset($data['onsite_pricing']) && $this->hasPricingData($data['onsite_pricing'])) {
            $this->updateOrCreateCoursePrice($course->id, DeliveryType::Onsite, $data['onsite_pricing']);
        }

        if (isset($data['online_pricing']) && $this->hasPricingData($data['online_pricing'])) {
            $this->updateOrCreateCoursePrice($course->id, DeliveryType::Online, $data['online_pricing']);
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

    protected function updateOrCreateCoursePrice(int $courseId, DeliveryType $deliveryType, array $pricingData): void
    {
        CoursePrice::updateOrCreate(
            [
                'course_id' => $courseId,
                'delivery_type' => $deliveryType,
            ],
            [
                'branch_id' => null,
                'pricing_mode' => $pricingData['pricing_mode'] ?? 'course_total',
                'price' => $pricingData['price'] ?? null,
                'session_price' => $pricingData['session_price'] ?? null,
                'sessions_count' => $pricingData['sessions_count'] ?? null,
                'is_active' => $pricingData['is_active'] ?? true,
            ]
        );
    }
}
