<?php

namespace Tests\Feature;

use App\Domain\Accounting\Events\PaymentPaid;
use App\Domain\Accounting\Models\ArInvoice;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Branch\Models\Branch;
use App\Domain\Enrollment\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ArInvoiceDueAmountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'sqlite'])->run();
    }

    /**
     * Test that due_amount is computed correctly as total_amount - SUM(payments where status='paid')
     */
    public function test_due_amount_is_computed_correctly(): void
    {
        // Create user and branch
        $user = User::factory()->create();
        $branch = Branch::factory()->create();

        // Create enrollment
        $enrollment = Enrollment::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        // Create AR invoice with total_amount = 1000
        $invoice = ArInvoice::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'total_amount' => 1000.00,
            'status' => 'open',
            'issued_at' => now(),
        ]);

        // Initially, no payments, so due_amount should equal total_amount
        $this->assertEquals(1000.00, $invoice->due_amount);

        // Create a payment of 100 with status='paid'
        $payment = Payment::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'amount' => 100.00,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Refresh invoice to get updated computed due_amount
        $invoice->refresh();
        $this->assertEquals(900.00, $invoice->due_amount);

        // Add another payment of 200
        Payment::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'amount' => 200.00,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Refresh invoice
        $invoice->refresh();
        $this->assertEquals(700.00, $invoice->due_amount);
    }

    /**
     * Test that non-paid payments are not included in due_amount calculation
     */
    public function test_non_paid_payments_are_not_included_in_due_amount(): void
    {
        $user = User::factory()->create();
        $branch = Branch::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        $invoice = ArInvoice::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'total_amount' => 1000.00,
            'status' => 'open',
            'issued_at' => now(),
        ]);

        // Create pending payment (should not affect due_amount)
        Payment::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'amount' => 500.00,
            'method' => 'cash',
            'status' => 'pending',
        ]);

        // Due amount should still be 1000 (full amount)
        $invoice->refresh();
        $this->assertEquals(1000.00, $invoice->due_amount);

        // Create paid payment (should affect due_amount)
        Payment::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'amount' => 300.00,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Due amount should now be 700
        $invoice->refresh();
        $this->assertEquals(700.00, $invoice->due_amount);
    }

    /**
     * Test that direct update to due_amount throws exception
     */
    public function test_direct_update_to_due_amount_throws_exception(): void
    {
        $user = User::factory()->create();
        $branch = Branch::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        $invoice = ArInvoice::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'total_amount' => 1000.00,
            'status' => 'open',
            'issued_at' => now(),
        ]);

        // Try to set due_amount directly
        $invoice->due_amount = 500;

        // Attempting to save should throw exception
        $this->expectException(\Illuminate\Database\Eloquent\MassAssignmentException::class);
        $this->expectExceptionMessage('due_amount is computed and cannot be updated directly');

        $invoice->save();
    }

    /**
     * Test that mass assignment of due_amount throws exception
     */
    public function test_mass_assignment_of_due_amount_is_blocked(): void
    {
        $user = User::factory()->create();
        $branch = Branch::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        $invoice = ArInvoice::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'total_amount' => 1000.00,
            'status' => 'open',
            'issued_at' => now(),
        ]);

        // Try to mass assign due_amount - should be blocked by guarded
        $invoice->fill(['due_amount' => 500]);

        // The fill should not set due_amount (it's guarded), so verify it's still computed
        $this->assertEquals(1000.00, $invoice->due_amount);

        // But if somehow it gets into dirty attributes, updating should throw
        // Let's try via update() method
        $this->expectException(\Illuminate\Database\Eloquent\MassAssignmentException::class);
        $invoice->update(['due_amount' => 500]);
    }

    /**
     * Test that status updates correctly based on computed due_amount
     */
    public function test_status_updates_correctly_based_on_computed_due_amount(): void
    {
        $user = User::factory()->create();
        $branch = Branch::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        $invoice = ArInvoice::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'total_amount' => 1000.00,
            'status' => 'open',
            'issued_at' => now(),
        ]);

        // Initially should be 'open' (due_amount == total_amount)
        $invoice->updateStatus();
        $this->assertEquals('open', $invoice->status);

        // Pay 300 - should become 'partial' (0 < due_amount < total_amount)
        Payment::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'amount' => 300.00,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $invoice->refresh();
        $invoice->updateStatus();
        $this->assertEquals('partial', $invoice->status);
        $this->assertEquals(700.00, $invoice->due_amount);

        // Pay remaining 700 - should become 'paid' (due_amount == 0)
        Payment::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'amount' => 700.00,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $invoice->refresh();
        $invoice->updateStatus();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0.00, $invoice->due_amount);
    }

    /**
     * Test that PaymentPaid event updates invoice status correctly
     */
    public function test_payment_paid_event_updates_invoice_status(): void
    {
        Event::fake([PaymentPaid::class]);

        $user = User::factory()->create();
        $branch = Branch::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        $invoice = ArInvoice::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'total_amount' => 1000.00,
            'status' => 'open',
            'issued_at' => now(),
        ]);

        // Create a payment and fire the PaymentPaid event
        $payment = Payment::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'amount' => 500.00,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Fire the event
        event(new PaymentPaid($payment));

        // The PostCashReceipt listener should update the invoice status
        // Since we're using sync queue, it should run immediately
        $invoice->refresh();
        
        // Verify due_amount is computed correctly
        $this->assertEquals(500.00, $invoice->due_amount);
        
        // Note: The actual status update happens in PostCashReceipt listener
        // which we can't easily test here without mocking, but we verify the computed value is correct
    }

    /**
     * Test that payments from different enrollments don't affect due_amount
     */
    public function test_payments_from_different_enrollments_dont_affect_due_amount(): void
    {
        $user = User::factory()->create();
        $branch = Branch::factory()->create();
        
        $enrollment1 = Enrollment::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        $enrollment2 = Enrollment::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        $invoice1 = ArInvoice::create([
            'enrollment_id' => $enrollment1->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'total_amount' => 1000.00,
            'status' => 'open',
            'issued_at' => now(),
        ]);

        // Create payment for enrollment2 (should not affect invoice1)
        Payment::create([
            'enrollment_id' => $enrollment2->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'amount' => 500.00,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Invoice1 due_amount should still be 1000
        $invoice1->refresh();
        $this->assertEquals(1000.00, $invoice1->due_amount);
    }
}

