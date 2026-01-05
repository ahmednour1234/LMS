<?php

/**
 * Tinker snippet to test CoursePrice resolution
 * 
 * Usage: Copy and paste into `php artisan tinker` or run as a standalone script
 * 
 * This script tests the pricing resolution logic:
 * 1. Creates a CoursePrice with delivery_type='onsite'
 * 2. Attempts resolve with registration_type='online' (should fail - expected)
 * 3. Attempts resolve with registration_type='onsite' + correct branch (should succeed)
 */

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CoursePrice;
use App\Domain\Branch\Models\Branch;
use App\Domain\Training\Enums\DeliveryType;
use App\Services\PricingService;

// Get or create a test course
$course = Course::first();
if (!$course) {
    echo "ERROR: No courses found. Please create a course first.\n";
    exit(1);
}

// Get or create a test branch
$branch = Branch::first();
if (!$branch) {
    echo "ERROR: No branches found. Please create a branch first.\n";
    exit(1);
}

echo "=== Testing CoursePrice Resolution ===\n\n";
echo "Course ID: {$course->id}\n";
echo "Branch ID: {$branch->id}\n\n";

// Clean up any existing test prices
CoursePrice::where('course_id', $course->id)
    ->where('branch_id', $branch->id)
    ->where('delivery_type', DeliveryType::Onsite)
    ->delete();

// Create a CoursePrice with delivery_type='onsite'
$coursePrice = CoursePrice::create([
    'course_id' => $course->id,
    'branch_id' => $branch->id,
    'delivery_type' => DeliveryType::Onsite,
    'price' => 5000.00,
    'is_active' => true,
    'allow_installments' => false,
]);

echo "✓ Created CoursePrice:\n";
echo "  - ID: {$coursePrice->id}\n";
echo "  - Course ID: {$coursePrice->course_id}\n";
echo "  - Branch ID: {$coursePrice->branch_id}\n";
echo "  - Delivery Type: {$coursePrice->delivery_type->value}\n";
echo "  - Price: {$coursePrice->price}\n";
echo "  - Active: " . ($coursePrice->is_active ? 'Yes' : 'No') . "\n\n";

$pricingService = app(PricingService::class);

// Test 1: Attempt resolve with registration_type='online' (should fail - expected)
echo "Test 1: Resolve with registration_type='online' (should fail - expected)\n";
echo "---\n";
$result1 = $pricingService->resolveCoursePrice(
    $course->id,
    $branch->id,
    'online'
);

if ($result1) {
    echo "✗ UNEXPECTED: Found price (ID: {$result1->id})\n";
} else {
    echo "✓ EXPECTED: No price found (onsite CoursePrice doesn't match online registration_type)\n";
}
echo "\n";

// Test 2: Attempt resolve with registration_type='onsite' + correct branch (should succeed)
echo "Test 2: Resolve with registration_type='onsite' + correct branch (should succeed)\n";
echo "---\n";
$result2 = $pricingService->resolveCoursePrice(
    $course->id,
    $branch->id,
    'onsite'
);

if ($result2) {
    echo "✓ SUCCESS: Found price\n";
    echo "  - CoursePrice ID: {$result2->id}\n";
    echo "  - Price: {$result2->price}\n";
    echo "  - Delivery Type: {$result2->delivery_type->value}\n";
} else {
    echo "✗ FAILED: No price found (this should have succeeded!)\n";
}
echo "\n";

// Test 3: Attempt resolve with registration_type='onsite' + wrong branch (should fail)
echo "Test 3: Resolve with registration_type='onsite' + wrong branch (should fail)\n";
echo "---\n";
$wrongBranch = Branch::where('id', '!=', $branch->id)->first();
if ($wrongBranch) {
    $result3 = $pricingService->resolveCoursePrice(
        $course->id,
        $wrongBranch->id,
        'onsite'
    );
    
    if ($result3) {
        echo "⚠ Found price with wrong branch (may be a global price without branch_id)\n";
        echo "  - CoursePrice ID: {$result3->id}\n";
        echo "  - Branch ID: " . ($result3->branch_id ? $result3->branch_id : 'null (global)') . "\n";
    } else {
        echo "✓ EXPECTED: No price found (wrong branch)\n";
    }
} else {
    echo "⚠ Skipped: Only one branch exists\n";
}
echo "\n";

// Test 4: Attempt resolve with registration_type='online' + null branch (should try global fallback)
echo "Test 4: Resolve with registration_type='online' + null branch (should try global fallback)\n";
echo "---\n";
$result4 = $pricingService->resolveCoursePrice(
    $course->id,
    null,
    'online'
);

if ($result4) {
    echo "✓ Found global price\n";
    echo "  - CoursePrice ID: {$result4->id}\n";
    echo "  - Branch ID: " . ($result4->branch_id ? $result4->branch_id : 'null (global)') . "\n";
    echo "  - Delivery Type: " . ($result4->delivery_type ? $result4->delivery_type->value : 'null') . "\n";
} else {
    echo "✓ No global price found (expected if no global prices exist)\n";
}
echo "\n";

echo "=== Test Complete ===\n";
echo "\n";
echo "Check the logs for [PRICING_DEBUG] entries to see detailed query information.\n";
echo "Run: tail -f storage/logs/laravel.log | grep PRICING_DEBUG\n";

