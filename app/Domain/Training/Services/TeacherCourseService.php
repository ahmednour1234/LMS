<?php

namespace App\Http\Services;

use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CoursePrice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TeacherCourseService
{
    public function myCourses(int $teacherId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Course::query()->where('owner_teacher_id', $teacherId);

        // q search
        if (!empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(function (Builder $q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$term}%"]);
            });
        }

        // active default 1
        $active = $filters['active'] ?? 1;
        if ($active !== null && $active !== '') {
            $query->where('is_active', (bool) $active);
        }

        if (!empty($filters['program_id'])) {
            $query->where('program_id', (int) $filters['program_id']);
        }

        if (!empty($filters['delivery_type'])) {
            $query->where('delivery_type', $filters['delivery_type']);
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'name'   => $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) ASC"),
            default  => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
        };

        return $query->paginate($perPage);
    }

    public function findTeacherCourse(int $teacherId, int $courseId): ?Course
    {
        return Course::query()
            ->where('owner_teacher_id', $teacherId)
            ->where('id', $courseId)
            ->first();
    }

    public function createCourse(int $teacherId, array $data): Course
    {
        $prices = $data['prices'] ?? null;   // NEW
        unset($data['prices']);

        $data['owner_teacher_id'] = $teacherId;
        $data['is_active'] = $data['is_active'] ?? true;

        $course = Course::create($data);

        // create multiple prices in same request
        if (is_array($prices)) {
            foreach ($prices as $priceData) {
                $this->upsertPriceForCourseByType($course, $priceData);
            }
        }

        return $course;
    }

    /**
     * Upsert CoursePrice by provided delivery_type (online/onsite/hybrid) for branch_id null
     */
    public function upsertPriceForCourseByType(Course $course, array $pricingData): CoursePrice
    {
        $type = $pricingData['delivery_type'] ?? null;
        if (!$type) {
            // لو حبيت ترمي exception هنا - لكن request rules already guarantee it
            $type = $course->delivery_type?->value ?? (string) $course->delivery_type;
        }

        $deliveryType = $type instanceof \App\Domain\Training\Enums\DeliveryType
            ? $type
            : \App\Domain\Training\Enums\DeliveryType::from((string) $type);

        return CoursePrice::updateOrCreate(
            [
                'course_id' => $course->id,
                'branch_id' => null,
                'delivery_type' => $deliveryType,
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


    public function toggleActive(Course $course): Course
    {
        $course->is_active = !$course->is_active;
        $course->save();
        $course->refresh();
        return $course;
    }

    /**
     * Create/Update CoursePrice for course delivery_type (branch_id null).
     * Uses updateOrCreate to avoid duplicates.
     */
    public function upsertPriceForCourse(Course $course, array $pricingData): CoursePrice
    {
        $deliveryType = $course->delivery_type instanceof DeliveryType
            ? $course->delivery_type
            : DeliveryType::from((string) $course->delivery_type);

        return CoursePrice::updateOrCreate(
            [
                'course_id' => $course->id,
                'branch_id' => null,
                'delivery_type' => $deliveryType,
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
