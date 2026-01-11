<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Domain\Training\Models\CoursePrice;
use App\Filament\Admin\Resources\CourseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    protected array $priceData = [];

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

        $this->priceData = $this->extractPriceData($data);

        return $data;
    }

    protected function extractPriceData(array $data): array
    {
        $priceFields = [
            'price_delivery_type',
            'pricing_mode',
            'price',
            'session_price',
            'sessions_count',
            'allow_installments',
            'min_down_payment',
            'max_installments',
            'price_is_active',
        ];

        $priceData = [];
        foreach ($priceFields as $field) {
            if (isset($data[$field])) {
                $priceData[$field] = $data[$field];
            }
        }

        return $priceData;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $course = parent::handleRecordCreation($data);

            if ($this->shouldCreatePrice()) {
                $this->createCoursePrice($course);
            }

            return $course;
        });
    }

    protected function shouldCreatePrice(): bool
    {
        if (empty($this->priceData)) {
            return false;
        }

        $pricingMode = $this->priceData['pricing_mode'] ?? null;
        if (!$pricingMode) {
            return false;
        }

        if (in_array($pricingMode, ['course_total', 'both'])) {
            if (!empty($this->priceData['price']) && (float) $this->priceData['price'] > 0) {
                return true;
            }
        }

        if (in_array($pricingMode, ['per_session', 'both'])) {
            if (!empty($this->priceData['session_price']) && (float) $this->priceData['session_price'] > 0) {
                return true;
            }
        }

        return false;
    }

    protected function createCoursePrice(\Illuminate\Database\Eloquent\Model $course): void
    {
        $priceData = [
            'course_id' => $course->id,
            'branch_id' => null,
            'delivery_type' => $this->priceData['price_delivery_type'] ?? null,
            'pricing_mode' => $this->priceData['pricing_mode'] ?? 'course_total',
            'price' => $this->priceData['price'] ?? null,
            'session_price' => $this->priceData['session_price'] ?? null,
            'sessions_count' => $this->priceData['sessions_count'] ?? null,
            'allow_installments' => $this->priceData['allow_installments'] ?? false,
            'min_down_payment' => $this->priceData['min_down_payment'] ?? null,
            'max_installments' => $this->priceData['max_installments'] ?? null,
            'is_active' => $this->priceData['price_is_active'] ?? true,
        ];

        CoursePrice::create($priceData);
    }
}
