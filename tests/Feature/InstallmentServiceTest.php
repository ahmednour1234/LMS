<?php

namespace Tests\Feature;

use App\Domain\Accounting\Models\ArInstallment;
use App\Domain\Accounting\Models\ArInvoice;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Branch\Models\Branch;
use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Student\Models\Student;
use App\Domain\Training\Models\Course;
use App\Models\User;
use App\Services\InstallmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InstallmentService $service;
    protected ArInvoice $invoice;
    protected Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InstallmentService();
        
        // Create test data
        $user = User::factory()->create();
        $branch = Branch::factory()->create();
        $course = Course::factory()->create(['branch_id' => $branch->id]);
        $student = Student::factory()->create();
        
        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'branch_id' => $branch->id,
            'total_amount' => 1000.000,
        ]);

        $this->invoice = ArInvoice::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'total_amount' => 1000.000,
            'status' => 'open',
            'issued_at' => now(),
        ]);
    }

    /**
     * Test schedule generation with down payment
     */
    public function test_generates_schedule_with_down_payment(): void
    {
        $installments = $this->service->generateSchedule(
            invoice: $this->invoice,
            downPayment: 200.000,
            numberOfInstallments: 4,
            interval: 'monthly',
            startDate: Carbon::parse('2026-02-01')
        );

        // Down payment + 4 installments = 5 total
        $this->assertCount(5, $installments);

        // First is down payment (due today)
        $first = $installments->first();
        $this->assertEquals(1, $first->installment_no);
        $this->assertEquals(200.000, (float) $first->amount);

        // Remaining 800 / 4 = 200 each
        $remaining = $installments->skip(1);
        foreach ($remaining as $installment) {
            $this->assertEquals(200.000, (float) $installment->amount);
        }

        // Total should equal invoice total
        $total = $installments->sum('amount');
        $this->assertEquals(1000.000, $total);
    }

    /**
     * Test schedule generation without down payment
     */
    public function test_generates_schedule_without_down_payment(): void
    {
        $installments = $this->service->generateSchedule(
            invoice: $this->invoice,
            downPayment: 0,
            numberOfInstallments: 5,
            interval: 'monthly',
            startDate: Carbon::parse('2026-02-01')
        );

        $this->assertCount(5, $installments);

        // 1000 / 5 = 200 each
        foreach ($installments as $installment) {
            $this->assertEquals(200.000, (float) $installment->amount);
        }
    }

    /**
     * Test rounding - last installment absorbs difference
     */
    public function test_last_installment_absorbs_rounding_difference(): void
    {
        // 1000 / 3 = 333.333... which doesn't divide evenly
        $installments = $this->service->generateSchedule(
            invoice: $this->invoice,
            downPayment: 0,
            numberOfInstallments: 3,
            interval: 'monthly'
        );

        $this->assertCount(3, $installments);

        // Total should still equal invoice total exactly
        $total = $installments->sum('amount');
        $this->assertEquals(1000.000, round($total, 3));

        // Last installment should be different (absorbs rounding)
        $amounts = $installments->pluck('amount')->map(fn($a) => (float) $a)->toArray();
        $lastAmount = array_pop($amounts);
        
        // First installments should be equal
        $this->assertEquals($amounts[0], $amounts[1]);
    }

    /**
     * Test monthly interval dates
     */
    public function test_monthly_interval_dates(): void
    {
        $startDate = Carbon::parse('2026-02-01');
        
        $installments = $this->service->generateSchedule(
            invoice: $this->invoice,
            downPayment: 0,
            numberOfInstallments: 3,
            interval: 'monthly',
            startDate: $startDate
        );

        $dates = $installments->pluck('due_date')->toArray();
        
        $this->assertEquals('2026-02-01', $dates[0]->format('Y-m-d'));
        $this->assertEquals('2026-03-01', $dates[1]->format('Y-m-d'));
        $this->assertEquals('2026-04-01', $dates[2]->format('Y-m-d'));
    }

    /**
     * Test weekly interval dates
     */
    public function test_weekly_interval_dates(): void
    {
        $startDate = Carbon::parse('2026-02-01');
        
        $installments = $this->service->generateSchedule(
            invoice: $this->invoice,
            downPayment: 0,
            numberOfInstallments: 3,
            interval: 'weekly',
            startDate: $startDate
        );

        $dates = $installments->pluck('due_date')->toArray();
        
        $this->assertEquals('2026-02-01', $dates[0]->format('Y-m-d'));
        $this->assertEquals('2026-02-08', $dates[1]->format('Y-m-d'));
        $this->assertEquals('2026-02-15', $dates[2]->format('Y-m-d'));
    }

    /**
     * Test payment allocation - earliest due first
     */
    public function test_allocates_payment_earliest_due_first(): void
    {
        // Generate installments
        $this->service->generateSchedule(
            invoice: $this->invoice,
            downPayment: 0,
            numberOfInstallments: 4,
            interval: 'monthly',
            startDate: Carbon::parse('2026-02-01')
        );

        // Create a payment for 500 (covers 2 installments of 250 each)
        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->enrollment->user_id,
            'branch_id' => $this->enrollment->branch_id,
            'amount' => 500.000,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $allocation = $this->service->allocatePayment($payment, $this->invoice);

        $this->assertEquals(500.000, $allocation['total_allocated']);
        $this->assertEquals(0, $allocation['unallocated']);
        $this->assertCount(2, $allocation['allocations']);

        // First two installments should be paid
        $installments = $this->invoice->arInstallments()->orderBy('installment_no')->get();
        $this->assertEquals('paid', $installments[0]->status);
        $this->assertEquals('paid', $installments[1]->status);
        $this->assertEquals('pending', $installments[2]->status);
        $this->assertEquals('pending', $installments[3]->status);
    }

    /**
     * Test partial payment allocation
     */
    public function test_allocates_partial_payment(): void
    {
        $this->service->generateSchedule(
            invoice: $this->invoice,
            downPayment: 0,
            numberOfInstallments: 4,
            interval: 'monthly'
        );

        // Create a payment for 150 (partial of first installment of 250)
        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->enrollment->user_id,
            'branch_id' => $this->enrollment->branch_id,
            'amount' => 150.000,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $allocation = $this->service->allocatePayment($payment, $this->invoice);

        $this->assertEquals(150.000, $allocation['total_allocated']);

        // First installment should be partially paid
        $firstInstallment = $this->invoice->arInstallments()->orderBy('installment_no')->first();
        $this->assertEquals(150.000, (float) $firstInstallment->paid_amount);
        $this->assertNotEquals('paid', $firstInstallment->status); // Still pending
    }

    /**
     * Test validate schedule params - valid
     */
    public function test_validate_schedule_params_valid(): void
    {
        $result = $this->service->validateScheduleParams(
            total: 1000,
            downPayment: 200,
            numberOfInstallments: 4
        );

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test validate schedule params - down payment exceeds total
     */
    public function test_validate_down_payment_exceeds_total(): void
    {
        $result = $this->service->validateScheduleParams(
            total: 1000,
            downPayment: 1500,
            numberOfInstallments: 4
        );

        $this->assertFalse($result['valid']);
        $this->assertContains('Down payment cannot exceed invoice total.', $result['errors']);
    }

    /**
     * Test validate schedule params - installments less than 1
     */
    public function test_validate_installments_minimum(): void
    {
        $result = $this->service->validateScheduleParams(
            total: 1000,
            downPayment: 0,
            numberOfInstallments: 0
        );

        $this->assertFalse($result['valid']);
        $this->assertContains('Number of installments must be at least 1.', $result['errors']);
    }

    /**
     * Test validate schedule params - exceeds max installments
     */
    public function test_validate_exceeds_max_installments(): void
    {
        $result = $this->service->validateScheduleParams(
            total: 1000,
            downPayment: 0,
            numberOfInstallments: 12,
            maxInstallments: 6
        );

        $this->assertFalse($result['valid']);
    }

    /**
     * Test preview schedule without creating records
     */
    public function test_preview_schedule(): void
    {
        $preview = $this->service->previewSchedule(
            total: 1000,
            downPayment: 200,
            numberOfInstallments: 4,
            interval: 'monthly',
            startDate: Carbon::parse('2026-02-01')
        );

        $this->assertTrue($preview['valid']);
        $this->assertCount(5, $preview['schedule']); // Down payment + 4 installments

        // Verify summary
        $this->assertEquals(1000, $preview['summary']['total']);
        $this->assertEquals(200, $preview['summary']['down_payment']);
        $this->assertEquals(800, $preview['summary']['financed_amount']);
        $this->assertEquals(4, $preview['summary']['number_of_installments']);
        $this->assertEquals(200, $preview['summary']['installment_amount']);

        // No actual records should be created
        $this->assertEquals(0, ArInstallment::count());
    }

    /**
     * Test installment summary
     */
    public function test_get_installment_summary(): void
    {
        $this->service->generateSchedule(
            invoice: $this->invoice,
            downPayment: 0,
            numberOfInstallments: 4,
            interval: 'monthly'
        );

        // Pay first installment
        $firstInstallment = $this->invoice->arInstallments()->orderBy('installment_no')->first();
        $firstInstallment->update([
            'paid_amount' => $firstInstallment->amount,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $summary = $this->service->getInstallmentSummary($this->invoice);

        $this->assertTrue($summary['has_installments']);
        $this->assertEquals(4, $summary['total_installments']);
        $this->assertEquals(1, $summary['paid_installments']);
        $this->assertEquals(3, $summary['pending_installments']);
        $this->assertEquals(1000.000, $summary['total_amount']);
        $this->assertEquals(250.000, $summary['paid_amount']);
        $this->assertEquals(750.000, $summary['remaining_amount']);
    }

    /**
     * Test update overdue status
     */
    public function test_update_overdue_status(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-15'));

        $this->service->generateSchedule(
            invoice: $this->invoice,
            downPayment: 0,
            numberOfInstallments: 4,
            interval: 'monthly',
            startDate: Carbon::parse('2026-02-01')
        );

        // First two installments should be overdue (Feb 1 and Mar 1)
        $count = $this->service->updateOverdueStatus($this->invoice);

        $this->assertEquals(2, $count);

        $installments = $this->invoice->arInstallments()->orderBy('installment_no')->get();
        $this->assertEquals('overdue', $installments[0]->status);
        $this->assertEquals('overdue', $installments[1]->status);
        $this->assertEquals('pending', $installments[2]->status);
        $this->assertEquals('pending', $installments[3]->status);

        Carbon::setTestNow(); // Reset
    }
}

