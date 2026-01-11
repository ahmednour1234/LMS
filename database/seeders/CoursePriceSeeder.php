<?php

namespace Database\Seeders;

use App\Domain\Branch\Models\Branch;
use App\Domain\Training\Enums\DeliveryType;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CoursePrice;
use Illuminate\Database\Seeder;

class CoursePriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::all();
        $branches = Branch::all();

        if ($courses->isEmpty()) {
            $this->command->warn('No courses found. Please seed courses first.');
            return;
        }

        $deliveryTypes = [DeliveryType::Onsite, DeliveryType::Online];
        $pricingModes = ['course_total', 'per_session', 'both'];

        foreach ($courses as $course) {
            // Create prices for each branch and delivery type
            foreach ($branches as $branch) {
                foreach ($deliveryTypes as $deliveryType) {
                    // Randomly select pricing mode
                    $pricingMode = $pricingModes[array_rand($pricingModes)];
                    
                    $priceData = [
                        'course_id' => $course->id,
                        'branch_id' => $branch->id,
                        'delivery_type' => $deliveryType,
                        'pricing_mode' => $pricingMode,
                        'is_active' => true,
                    ];

                    // Set price for course_total or both modes
                    if (in_array($pricingMode, ['course_total', 'both'])) {
                        $priceData['price'] = rand(500, 5000);
                        $priceData['allow_installments'] = rand(0, 1) === 1;

                        // If installments are allowed, set installment details
                        if ($priceData['allow_installments']) {
                            $priceData['min_down_payment'] = $priceData['price'] * (rand(20, 40) / 100);
                            $priceData['max_installments'] = rand(2, 6);
                        }
                    }

                    // Set session_price and sessions_count for per_session or both modes
                    if (in_array($pricingMode, ['per_session', 'both'])) {
                        $priceData['session_price'] = rand(50, 500);
                        $priceData['sessions_count'] = rand(5, 20);
                    }

                    // Set price to 0 when pricing_mode is per_session (price is required but not used)
                    if ($pricingMode === 'per_session') {
                        $priceData['price'] = 0;
                    }

                    CoursePrice::firstOrCreate(
                        [
                            'course_id' => $course->id,
                            'branch_id' => $branch->id,
                            'delivery_type' => $deliveryType,
                            'pricing_mode' => $pricingMode,
                        ],
                        $priceData
                    );
                }
            }
            
            // Also create global prices (no branch) for online
            foreach ([DeliveryType::Online] as $deliveryType) {
                $pricingMode = $pricingModes[array_rand($pricingModes)];
                
                $priceData = [
                    'course_id' => $course->id,
                    'branch_id' => null,
                    'delivery_type' => $deliveryType,
                    'pricing_mode' => $pricingMode,
                    'is_active' => true,
                ];

                if (in_array($pricingMode, ['course_total', 'both'])) {
                    $priceData['price'] = rand(500, 5000);
                    $priceData['allow_installments'] = rand(0, 1) === 1;

                    if ($priceData['allow_installments']) {
                        $priceData['min_down_payment'] = $priceData['price'] * (rand(20, 40) / 100);
                        $priceData['max_installments'] = rand(2, 6);
                    }
                }

                if (in_array($pricingMode, ['per_session', 'both'])) {
                    $priceData['session_price'] = rand(50, 500);
                    $priceData['sessions_count'] = rand(5, 20);
                }

                // Set price to 0 when pricing_mode is per_session (price is required but not used)
                if ($pricingMode === 'per_session') {
                    $priceData['price'] = 0;
                }

                CoursePrice::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'branch_id' => null,
                        'delivery_type' => $deliveryType,
                        'pricing_mode' => $pricingMode,
                    ],
                    $priceData
                );
            }
        }

        $this->command->info('Course prices seeded successfully!');
    }
}

