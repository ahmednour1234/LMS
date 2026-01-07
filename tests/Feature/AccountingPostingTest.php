<?php

namespace Tests\Feature;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\ArInvoice;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Models\JournalLine;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Services\AccountingService;
use App\Domain\Branch\Models\Branch;
use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Student\Models\Student;
use App\Domain\Training\Models\Course;
use App\Enums\JournalStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingPostingTest extends TestCase
{
    use RefreshDatabase;

    protected AccountingService $service;
    protected User $user;
    protected Branch $branch;
    protected Enrollment $enrollment;
    protected ArInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AccountingService();
        
        // Create required accounts
        $this->createRequiredAccounts();
        
        // Create test data
        $this->user = User::factory()->create();
        $this->branch = Branch::factory()->create();
        $course = Course::factory()->create(['branch_id' => $this->branch->id]);
        $student = Student::factory()->create();
        
        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'branch_id' => $this->branch->id,
            'total_amount' => 1000.000,
        ]);

        $this->invoice = ArInvoice::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'total_amount' => 1000.000,
            'status' => 'open',
            'issued_at' => now(),
        ]);
    }

    protected function createRequiredAccounts(): void
    {
        // Create accounts with codes matching config defaults
        Account::create([
            'code' => '1110',
            'name' => 'Cash',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        Account::create([
            'code' => '1120',
            'name' => 'Bank',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        Account::create([
            'code' => '1130',
            'name' => 'Accounts Receivable',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        Account::create([
            'code' => '2130',
            'name' => 'Deferred Revenue',
            'type' => 'liability',
            'normal_balance' => 'credit',
            'is_active' => true,
        ]);

        Account::create([
            'code' => '4110',
            'name' => 'Training Revenue',
            'type' => 'revenue',
            'normal_balance' => 'credit',
            'is_active' => true,
        ]);

        Account::create([
            'code' => '4910',
            'name' => 'Discount Given',
            'type' => 'contra_revenue',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);
    }

    /**
     * Test enrollment created journal entry is balanced
     */
    public function test_enrollment_created_journal_is_balanced(): void
    {
        $journal = $this->service->postEnrollmentCreated(
            amount: 1000.000,
            referenceType: 'enrollment',
            referenceId: $this->enrollment->id,
            branchId: $this->branch->id,
            description: 'Test enrollment',
            user: $this->user
        );

        $this->assertNotNull($journal);
        $this->assertEquals(JournalStatus::POSTED, $journal->status);

        // Verify balance
        $totalDebit = $journal->journalLines->sum('debit');
        $totalCredit = $journal->journalLines->sum('credit');
        
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertEquals(1000.000, $totalDebit);
    }

    /**
     * Test payment journal entry is balanced
     */
    public function test_payment_journal_is_balanced(): void
    {
        $journal = $this->service->postPayment(
            accountCode: '1110', // Cash
            amount: 500.000,
            referenceType: 'payment',
            referenceId: 1,
            branchId: $this->branch->id,
            description: 'Test payment',
            user: $this->user,
            arInvoiceId: $this->invoice->id
        );

        $this->assertNotNull($journal);
        
        $totalDebit = $journal->journalLines->sum('debit');
        $totalCredit = $journal->journalLines->sum('credit');
        
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertEquals(500.000, $totalDebit);
    }

    /**
     * Test course completion revenue recognition is balanced
     */
    public function test_course_completion_journal_is_balanced(): void
    {
        $journal = $this->service->postCourseCompletion(
            amount: 1000.000,
            referenceType: 'completion',
            referenceId: $this->enrollment->id,
            branchId: $this->branch->id,
            user: $this->user
        );

        $totalDebit = $journal->journalLines->sum('debit');
        $totalCredit = $journal->journalLines->sum('credit');
        
        $this->assertEquals($totalDebit, $totalCredit);

        // Verify accounts used
        $lines = $journal->journalLines;
        $debitLine = $lines->firstWhere('debit', '>', 0);
        $creditLine = $lines->firstWhere('credit', '>', 0);

        // Debit should be Deferred Revenue (2130)
        $this->assertEquals('2130', $debitLine->account->code);
        // Credit should be Training Revenue (4110)
        $this->assertEquals('4110', $creditLine->account->code);
    }

    /**
     * Test enrollment with discount creates proper entries
     */
    public function test_enrollment_with_discount_journal(): void
    {
        $journal = $this->service->postEnrollmentWithDiscount(
            grossAmount: 1000.000,
            discountAmount: 100.000,
            referenceType: 'enrollment',
            referenceId: $this->enrollment->id,
            branchId: $this->branch->id,
            user: $this->user
        );

        $this->assertNotNull($journal);

        // Verify balance
        $totalDebit = $journal->journalLines->sum('debit');
        $totalCredit = $journal->journalLines->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);

        // Verify AR is net amount (900)
        $arLine = $journal->journalLines
            ->filter(fn($l) => $l->account->code === '1130')
            ->first();
        $this->assertEquals(900.000, (float) $arLine->debit);

        // Verify Discount is 100
        $discountLine = $journal->journalLines
            ->filter(fn($l) => $l->account->code === '4910')
            ->first();
        $this->assertEquals(100.000, (float) $discountLine->debit);

        // Verify Deferred Revenue is gross (1000)
        $deferredLine = $journal->journalLines
            ->filter(fn($l) => $l->account->code === '2130')
            ->first();
        $this->assertEquals(1000.000, (float) $deferredLine->credit);
    }

    /**
     * Test journal reversal creates compensating entries
     */
    public function test_journal_reversal(): void
    {
        // Create original journal
        $originalJournal = $this->service->postEnrollmentCreated(
            amount: 1000.000,
            referenceType: 'enrollment',
            referenceId: $this->enrollment->id,
            branchId: $this->branch->id
        );

        // Reverse it
        $reversalJournal = $this->service->reverseJournal(
            $originalJournal,
            'Test reversal',
            $this->user
        );

        $this->assertNotNull($reversalJournal);
        $this->assertEquals('reversal', $reversalJournal->reference_type);
        $this->assertEquals($originalJournal->id, $reversalJournal->reference_id);

        // Verify reversal is balanced
        $totalDebit = $reversalJournal->journalLines->sum('debit');
        $totalCredit = $reversalJournal->journalLines->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);

        // Verify debits and credits are swapped
        $originalLines = $originalJournal->journalLines;
        $reversalLines = $reversalJournal->journalLines;

        foreach ($originalLines as $index => $originalLine) {
            $reversalLine = $reversalLines[$index];
            $this->assertEquals($originalLine->debit, $reversalLine->credit);
            $this->assertEquals($originalLine->credit, $reversalLine->debit);
        }
    }

    /**
     * Test double reversal is prevented
     */
    public function test_prevents_double_reversal(): void
    {
        $originalJournal = $this->service->postEnrollmentCreated(
            amount: 1000.000,
            referenceType: 'enrollment',
            referenceId: $this->enrollment->id,
            branchId: $this->branch->id
        );

        // First reversal should succeed
        $this->service->reverseJournal($originalJournal, 'First reversal');

        // Second reversal should fail
        $this->expectException(\App\Exceptions\BusinessException::class);
        $this->service->reverseJournal($originalJournal, 'Second reversal');
    }

    /**
     * Test invoice cancellation with no payments
     */
    public function test_cancel_invoice_without_payments(): void
    {
        // First create the enrollment journal
        $this->service->postEnrollmentCreated(
            amount: 1000.000,
            referenceType: 'enrollment',
            referenceId: $this->enrollment->id,
            branchId: $this->branch->id
        );

        // Cancel the invoice
        $reversalJournal = $this->service->cancelInvoice(
            $this->invoice,
            'Test cancellation',
            $this->user
        );

        $this->assertNotNull($reversalJournal);
        $this->assertEquals('canceled', $this->invoice->fresh()->status);
    }

    /**
     * Test invoice cancellation with payments is blocked
     */
    public function test_cancel_invoice_with_payments_blocked(): void
    {
        // Create a payment
        Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.000,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->expectException(\App\Exceptions\BusinessException::class);
        $this->expectExceptionMessage('Cannot cancel invoice with payments');

        $this->service->cancelInvoice($this->invoice, 'Should fail');
    }

    /**
     * Test unbalanced journal throws exception
     */
    public function test_unbalanced_journal_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not balanced');

        // Try to create journal with unbalanced entries
        $this->service->postJournalEntry(
            debits: [
                ['account_id' => Account::where('code', '1110')->first()->id, 'amount' => 100],
            ],
            credits: [
                ['account_id' => Account::where('code', '2130')->first()->id, 'amount' => 50], // Unbalanced!
            ],
            referenceType: 'test',
            referenceId: 1
        );
    }

    /**
     * Test invalid account code throws exception
     */
    public function test_invalid_account_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not found');

        $this->service->postPayment(
            accountCode: '9999', // Non-existent
            amount: 100.000,
            referenceType: 'payment',
            referenceId: 1
        );
    }

    /**
     * Test get account balance
     */
    public function test_get_account_balance(): void
    {
        // Post some transactions
        $this->service->postEnrollmentCreated(
            amount: 1000.000,
            referenceType: 'enrollment',
            referenceId: $this->enrollment->id,
            branchId: $this->branch->id
        );

        $balance = $this->service->getAccountBalance('1130'); // AR

        $this->assertEquals('1130', $balance['account_code']);
        $this->assertEquals('Accounts Receivable', $balance['account_name']);
        $this->assertEquals(1000.000, $balance['total_debit']);
        $this->assertEquals(0, $balance['total_credit']);
        $this->assertEquals(1000.000, $balance['balance']); // Debit normal balance
    }

    /**
     * Test validate accounts exist
     */
    public function test_validate_accounts_exist(): void
    {
        $result = $this->service->validateAccountsExist(['1110', '1130', '2130']);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['missing']);

        $result = $this->service->validateAccountsExist(['1110', '9999']);
        $this->assertFalse($result['valid']);
        $this->assertContains('9999', $result['missing']);
    }

    /**
     * Test refund journal entry
     */
    public function test_refund_journal_is_balanced(): void
    {
        $journal = $this->service->postRefund(
            accountCode: '1110', // Cash
            amount: 500.000,
            referenceType: 'refund',
            referenceId: 1,
            branchId: $this->branch->id,
            description: 'Test refund'
        );

        $totalDebit = $journal->journalLines->sum('debit');
        $totalCredit = $journal->journalLines->sum('credit');
        
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertEquals(500.000, $totalDebit);

        // Verify Deferred Revenue is debited (reversed)
        $deferredLine = $journal->journalLines
            ->filter(fn($l) => $l->account->code === '2130')
            ->first();
        $this->assertEquals(500.000, (float) $deferredLine->debit);
    }

    /**
     * Test transfer between accounts
     */
    public function test_transfer_journal_is_balanced(): void
    {
        $journal = $this->service->postTransfer(
            sourceAccountCode: '1110', // Cash
            destinationAccountCode: '1120', // Bank
            amount: 500.000,
            referenceType: 'transfer',
            referenceId: 1,
            branchId: $this->branch->id
        );

        $totalDebit = $journal->journalLines->sum('debit');
        $totalCredit = $journal->journalLines->sum('credit');
        
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertEquals(500.000, $totalDebit);

        // Verify Bank is debited
        $bankLine = $journal->journalLines
            ->filter(fn($l) => $l->account->code === '1120')
            ->first();
        $this->assertEquals(500.000, (float) $bankLine->debit);

        // Verify Cash is credited
        $cashLine = $journal->journalLines
            ->filter(fn($l) => $l->account->code === '1110')
            ->first();
        $this->assertEquals(500.000, (float) $cashLine->credit);
    }
}

