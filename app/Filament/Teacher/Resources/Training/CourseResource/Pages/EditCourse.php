<?php

namespace App\Filament\Teacher\Resources\Training\CourseResource\Pages;

use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\CoursePrice;
use App\Filament\Teacher\Resources\Training\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->record->owner_teacher_id !== auth('teacher')->id()) {
            abort(404);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $course = $this->record;
        
        $onlinePrice = CoursePrice::where('course_id', $course->id)
            ->where('delivery_type', DeliveryType::Online)
            ->whereNull('branch_id')
            ->latest()
            ->first();
            
        $hybridPrice = CoursePrice::where('course_id', $course->id)
            ->where('delivery_type', DeliveryType::Hybrid)
            ->whereNull('branch_id')
            ->latest()
            ->first();

        if ($onlinePrice) {
            $data['online_pricing'] = [
                'pricing_mode' => $onlinePrice->pricing_mode,
                'price' => $onlinePrice->price,
                'session_price' => $onlinePrice->session_price,
                'sessions_count' => $onlinePrice->sessions_count,
                'allow_installments' => $onlinePrice->allow_installments,
                'min_down_payment' => $onlinePrice->min_down_payment,
                'max_installments' => $onlinePrice->max_installments,
                'is_active' => $onlinePrice->is_active,
            ];
        }

        if ($hybridPrice) {
            $data['hybrid_pricing'] = [
                'pricing_mode' => $hybridPrice->pricing_mode,
                'price' => $hybridPrice->price,
                'session_price' => $hybridPrice->session_price,
                'sessions_count' => $hybridPrice->sessions_count,
                'allow_installments' => $hybridPrice->allow_installments,
                'min_down_payment' => $hybridPrice->min_down_payment,
                'max_installments' => $hybridPrice->max_installments,
                'is_active' => $hybridPrice->is_active,
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

        if (isset($data['online_pricing']) && $this->hasPricingData($data['online_pricing'])) {
            $this->updateOrCreateCoursePrice($course->id, DeliveryType::Online, $data['online_pricing']);
        }

        if (isset($data['hybrid_pricing']) && $this->hasPricingData($data['hybrid_pricing'])) {
            $this->updateOrCreateCoursePrice($course->id, DeliveryType::Hybrid, $data['hybrid_pricing']);
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
                'branch_id' => null,
            ],
            [
                'pricing_mode' => $pricingData['pricing_mode'] ?? 'course_total',
                'price' => $pricingData['price'] ?? null,
                'session_price' => $pricingData['session_price'] ?? null,
                'sessions_count' => $pricingData['sessions_count'] ?? null,
                'allow_installments' => $pricingData['allow_installments'] ?? false,
                'min_down_payment' => $pricingData['min_down_payment'] ?? null,
                'max_installments' => $pricingData['max_installments'] ?? null,
                'is_active' => $pricingData['is_active'] ?? true,
            ]
        );
    }
}
