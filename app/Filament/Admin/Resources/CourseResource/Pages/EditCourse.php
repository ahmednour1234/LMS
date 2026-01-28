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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $course = $this->record;
        
        $onsitePrice = CoursePrice::where('course_id', $course->id)
            ->where('delivery_type', DeliveryType::Onsite)
            ->first();
        
        $onlinePrice = CoursePrice::where('course_id', $course->id)
            ->where('delivery_type', DeliveryType::Online)
            ->first();

        if ($onsitePrice) {
            $data['onsite_pricing'] = [
                'pricing_mode' => $onsitePrice->pricing_mode,
                'price' => $onsitePrice->price,
                'session_price' => $onsitePrice->session_price,
                'sessions_count' => $onsitePrice->sessions_count,
                'is_active' => $onsitePrice->is_active,
            ];
        }

        if ($onlinePrice) {
            $data['online_pricing'] = [
                'pricing_mode' => $onlinePrice->pricing_mode,
                'price' => $onlinePrice->price,
                'session_price' => $onlinePrice->session_price,
                'sessions_count' => $onlinePrice->sessions_count,
                'is_active' => $onlinePrice->is_active,
            ];
        }

        return $data;
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
