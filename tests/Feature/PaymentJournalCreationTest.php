<?php

namespace Tests\Feature;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Models\JournalLine;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Services\JournalService;
use App\Domain\Branch\Models\Branch;
use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Student\Models\Student;
use App\Domain\Training\Models\Course;
use App\Enums\JournalStatus;
use App\Exceptions\BusinessException;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentJournalCreationTest extends TestCase
{
    use RefreshDatabase;

    protected JournalService $journalService;
    protected User $user;
    protected Branch $branch;
    protected Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->journalService = new JournalService();
        
        $this->createRequiredAccounts();
        $this->createRequiredSettings();
        
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
    }

    protected function createRequiredAccounts(): void
    {
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
            'code' => '4110',
            'name' => 'Training Revenue',
            'type' => 'revenue',
            'normal_balance' => 'credit',
            'is_active' => true,
        ]);

        Account::create([
            'code' => '5110',
            'name' => 'Expenses',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);
    }

    protected function createRequiredSettings(): void
    {
        Setting::create([
            'key' => 'default_cash_account_code',
            'value' => ['code' => '1110'],
            'group' => 'financial',
            'is_system' => true,
        ]);

        Setting::create([
            'key' => 'default_bank_account_code',
            'value' => ['code' => '1120'],
            'group' => 'financial',
            'is_system' => true,
        ]);

        Setting::create([
            'key' => 'default_revenue_account_code',
            'value' => ['code' => '4110'],
            'group' => 'financial',
            'is_system' => true,
        ]);

        Setting::create([
            'key' => 'default_expense_account_code',
            'value' => ['code' => '5110'],
            'group' => 'financial',
            'is_system' => true,
        ]);
    }

    public function test_payment_creation_creates_balanced_journal(): void
    {
        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.000,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $journal = $payment->journal;
        
        $this->assertNotNull($journal);
        $this->assertEquals('payment', $journal->reference_type);
        $this->assertEquals($payment->id, $journal->reference_id);
        $this->assertEquals(JournalStatus::DRAFT, $journal->status);

        $totalDebit = $journal->journalLines->sum('debit');
        $totalCredit = $journal->journalLines->sum('credit');
        
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertEquals(500.000, $totalDebit);
        $this->assertEquals(500.000, $totalCredit);

        $cashLine = $journal->journalLines->firstWhere('debit', '>', 0);
        $revenueLine = $journal->journalLines->firstWhere('credit', '>', 0);

        $this->assertEquals('1110', $cashLine->account->code);
        $this->assertEquals('4110', $revenueLine->account->code);
    }

    public function test_payment_with_bank_method_uses_bank_account(): void
    {
        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 750.000,
            'method' => 'bank',
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $journal = $payment->journal;
        $cashOrBankLine = $journal->journalLines->firstWhere('debit', '>', 0);

        $this->assertEquals('1120', $cashOrBankLine->account->code);
    }

    public function test_payment_with_gateway_method_uses_bank_account(): void
    {
        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 300.000,
            'method' => 'gateway',
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $journal = $payment->journal;
        $cashOrBankLine = $journal->journalLines->firstWhere('debit', '>', 0);

        $this->assertEquals('1120', $cashOrBankLine->account->code);
    }

    public function test_payment_status_pending_does_not_create_journal(): void
    {
        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.000,
            'method' => 'cash',
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $this->assertFalse($payment->journal()->exists());
    }

    public function test_payment_status_update_to_paid_creates_journal(): void
    {
        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.000,
            'method' => 'cash',
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $this->assertFalse($payment->journal()->exists());

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->assertTrue($payment->journal()->exists());
    }

    public function test_journal_creation_is_idempotent(): void
    {
        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.000,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $journal1 = $payment->journal;
        $journalCount1 = Journal::where('reference_type', 'payment')
            ->where('reference_id', $payment->id)
            ->count();

        $this->journalService->createForPayment($payment);
        
        $journal2 = $payment->fresh()->journal;
        $journalCount2 = Journal::where('reference_type', 'payment')
            ->where('reference_id', $payment->id)
            ->count();

        $this->assertEquals($journal1->id, $journal2->id);
        $this->assertEquals(1, $journalCount1);
        $this->assertEquals(1, $journalCount2);
    }

    public function test_journal_creation_is_transactional(): void
    {
        $initialJournalCount = Journal::count();

        try {
            DB::transaction(function () {
                $payment = Payment::create([
                    'enrollment_id' => $this->enrollment->id,
                    'user_id' => $this->user->id,
                    'branch_id' => $this->branch->id,
                    'amount' => 500.000,
                    'method' => 'invalid_method',
                    'status' => 'paid',
                    'paid_at' => now(),
                    'created_by' => $this->user->id,
                ]);

                $this->journalService->createForPayment($payment);
            });
        } catch (\Exception $e) {
            // Expected to fail due to invalid payment method
        }

        $this->assertEquals($initialJournalCount, Journal::count());
    }

    public function test_payment_cannot_be_edited_when_journal_posted(): void
    {
        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.000,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $journal = $payment->journal;
        $journal->update(['status' => JournalStatus::POSTED]);

        $this->assertFalse($payment->fresh()->canBeEdited());
    }

    public function test_payment_can_be_edited_when_journal_is_draft(): void
    {
        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.000,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($payment->canBeEdited());
    }

    public function test_payment_without_enrollment_uses_expense_account(): void
    {
        $payment = Payment::create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 200.000,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $journal = $payment->journal;
        $creditLine = $journal->journalLines->firstWhere('credit', '>', 0);

        $this->assertEquals('5110', $creditLine->account->code);
    }

    public function test_missing_account_setting_throws_exception(): void
    {
        Setting::where('key', 'default_cash_account_code')->delete();

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Setting \'default_cash_account_code\' not found');

        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.000,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => $this->user->id,
        ]);
    }

    public function test_invalid_account_code_throws_exception(): void
    {
        Setting::where('key', 'default_cash_account_code')->update([
            'value' => ['code' => '9999'],
        ]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Account with code \'9999\' not found');

        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.000,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => $this->user->id,
        ]);
    }

    public function test_journal_description_uses_payment_method(): void
    {
        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.000,
            'method' => 'bank',
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $journal = $payment->journal;
        $this->assertStringContainsString('Bank Transfer', $journal->description);
    }

    public function test_journal_date_uses_paid_at_when_available(): void
    {
        $paidAt = now()->subDays(5);
        
        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.000,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => $paidAt,
            'created_by' => $this->user->id,
        ]);

        $journal = $payment->journal;
        $this->assertEquals($paidAt->format('Y-m-d'), $journal->journal_date->format('Y-m-d'));
    }

    public function test_journal_date_falls_back_to_created_at(): void
    {
        $payment = Payment::create([
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.000,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => null,
            'created_by' => $this->user->id,
        ]);

        $journal = $payment->journal;
        $this->assertEquals($payment->created_at->format('Y-m-d'), $journal->journal_date->format('Y-m-d'));
    }
}
