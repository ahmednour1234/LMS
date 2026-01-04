<?php

namespace Database\Seeders;

use App\Domain\Branch\Models\Branch;
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

        $deliveryTypes = ['onsite', 'online', 'virtual'];

        foreach ($courses as $course) {
            // Create prices for each branch and delivery type
            foreach ($branches as $branch) {
                foreach ($deliveryTypes as $deliveryType) {
                    $priceData = [
                        'course_id' => $course->id,
                        'branch_id' => $branch->id,
                        'delivery_type' => $deliveryType,
                        'price' => rand(500, 5000),
                        'allow_installments' => rand(0, 1) === 1,
                        'is_active' => true,
                    ];

                    // If installments are allowed, set installment details
                    if ($priceData['allow_installments']) {
                        $priceData['min_down_payment'] = $priceData['price'] * (rand(20, 40) / 100);
                        $priceData['max_installments'] = rand(2, 6);
                    }

                    CoursePrice::firstOrCreate(
                        [
                            'course_id' => $course->id,
                            'branch_id' => $branch->id,
                            'delivery_type' => $deliveryType,
                        ],
                        $priceData
                    );
                }
            }
        }

        $this->command->info('Course prices seeded successfully!');
    }
}

