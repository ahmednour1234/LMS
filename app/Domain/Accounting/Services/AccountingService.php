<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\ArInvoice;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Models\JournalLine;
use App\Domain\Accounting\Models\Payment;
use App\Enums\JournalStatus;
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AccountingService
{
    /**
     * Get account code from config or use default.
     */
    protected function getAccountCode(string $key, string $default): string
    {
        return config("money.accounts.{$key}", $default);
    }

    public function postEnrollmentCreated(
        float $amount,
        string $referenceType,
        int $referenceId,
        ?int $branchId = null,
        ?string $description = null,
        ?User $user = null
    ): Journal {
        $arAccount = $this->findAccountByCode('1130');
        $deferredRevenue = $this->findAccountByCode('2130');

        $this->validateJournalBalance([$amount], [$amount]);

        return DB::transaction(function () use ($arAccount, $deferredRevenue, $amount, $referenceType, $referenceId, $branchId, $description, $user) {
            $journal = Journal::create([
                'reference' => $this->generateReference($referenceType, $referenceId),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'date' => now(),
                'description' => $description ?? "Enrollment created: AR invoice",
                'status' => JournalStatus::POSTED,
                'branch_id' => $branchId,
                'posted_at' => now(),
                'created_by' => $user?->id,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $arAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Accounts Receivable",
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $deferredRevenue->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Deferred Revenue",
            ]);

            return $journal;
        });
    }

    public function postPayment(
        string $accountCode,
        float $amount,
        string $referenceType,
        int $referenceId,
        ?int $branchId = null,
        ?string $description = null,
        ?User $user = null,
        ?int $arInvoiceId = null
    ): Journal {
        $account = $this->findAccountByCode($accountCode);
        $arAccount = $this->findAccountByCode('1130');

        $this->validateJournalBalance([$amount], [$amount]);

        return DB::transaction(function () use ($account, $arAccount, $amount, $referenceType, $referenceId, $branchId, $description, $user, $arInvoiceId) {
            $journal = Journal::create([
                'reference' => $this->generateReference($referenceType, $referenceId),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'date' => now(),
                'description' => $description ?? "Payment received via {$account->name}",
                'status' => JournalStatus::POSTED,
                'branch_id' => $branchId,
                'posted_at' => now(),
                'created_by' => $user?->id,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $account->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Payment received",
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $arAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Accounts Receivable",
            ]);

            // Update AR invoice status if invoice ID provided
            if ($arInvoiceId) {
                $this->updateArInvoiceStatus($arInvoiceId);
            }

            return $journal;
        });
    }

    public function postCourseCompletion(
        float $amount,
        string $referenceType,
        int $referenceId,
        ?int $branchId = null,
        ?string $description = null,
        ?User $user = null
    ): Journal {
        $deferredRevenue = $this->findAccountByCode('2130');
        $trainingRevenue = $this->findAccountByCode('4110');

        $this->validateJournalBalance([$amount], [$amount]);

        return DB::transaction(function () use ($deferredRevenue, $trainingRevenue, $amount, $referenceType, $referenceId, $branchId, $description, $user) {
            $journal = Journal::create([
                'reference' => $this->generateReference($referenceType, $referenceId),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'date' => now(),
                'description' => $description ?? "Course completion revenue recognition",
                'status' => JournalStatus::POSTED,
                'branch_id' => $branchId,
                'posted_at' => now(),
                'created_by' => $user?->id,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $deferredRevenue->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Deferred revenue recognition",
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $trainingRevenue->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Training revenue earned",
            ]);

            return $journal;
        });
    }

    public function postRefund(
        string $accountCode,
        float $amount,
        string $referenceType,
        int $referenceId,
        ?int $branchId = null,
        ?string $description = null,
        ?User $user = null
    ): Journal {
        $account = $this->findAccountByCode($accountCode);
        $deferredRevenue = $this->findAccountByCode('2130');

        $this->validateJournalBalance([$amount], [$amount]);

        return DB::transaction(function () use ($account, $deferredRevenue, $amount, $referenceType, $referenceId, $branchId, $description, $user) {
            $journal = Journal::create([
                'reference' => $this->generateReference($referenceType, $referenceId),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'date' => now(),
                'description' => $description ?? "Refund via {$account->name}",
                'status' => JournalStatus::POSTED,
                'branch_id' => $branchId,
                'posted_at' => now(),
                'created_by' => $user?->id,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $deferredRevenue->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Deferred revenue reversal",
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $account->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Refund payment",
            ]);

            return $journal;
        });
    }

    public function postExpense(
        string $expenseAccountCode,
        string $paymentAccountCode,
        float $amount,
        string $referenceType,
        int $referenceId,
        ?int $branchId = null,
        ?string $description = null,
        ?int $costCenterId = null,
        ?User $user = null
    ): Journal {
        $expenseAccount = $this->findAccountByCode($expenseAccountCode);
        $paymentAccount = $this->findAccountByCode($paymentAccountCode);

        $this->validateJournalBalance([$amount], [$amount]);

        return DB::transaction(function () use ($expenseAccount, $paymentAccount, $amount, $referenceType, $referenceId, $branchId, $description, $costCenterId, $user) {
            $journal = Journal::create([
                'reference' => $this->generateReference($referenceType, $referenceId),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'date' => now(),
                'description' => $description ?? "Expense payment: {$expenseAccount->name}",
                'status' => JournalStatus::POSTED,
                'branch_id' => $branchId,
                'posted_at' => now(),
                'created_by' => $user?->id,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $expenseAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Expense recorded",
                'cost_center_id' => $costCenterId,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $paymentAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Payment made",
            ]);

            return $journal;
        });
    }

    public function postJournalEntry(
        array $debits,
        array $credits,
        string $referenceType,
        int $referenceId,
        ?int $branchId = null,
        ?string $description = null,
        ?User $user = null
    ): Journal {
        $totalDebit = array_sum(array_column($debits, 'amount'));
        $totalCredit = array_sum(array_column($credits, 'amount'));

        $this->validateJournalBalance(
            array_column($debits, 'amount'),
            array_column($credits, 'amount')
        );

        return DB::transaction(function () use ($debits, $credits, $referenceType, $referenceId, $branchId, $description, $user) {
            $journal = Journal::create([
                'reference' => $this->generateReference($referenceType, $referenceId),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'date' => now(),
                'description' => $description ?? "Manual journal entry",
                'status' => JournalStatus::POSTED,
                'branch_id' => $branchId,
                'posted_at' => now(),
                'created_by' => $user?->id,
            ]);

            foreach ($debits as $debit) {
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $debit['account_id'],
                    'debit' => $debit['amount'],
                    'credit' => 0,
                    'description' => $debit['description'] ?? null,
                    'cost_center_id' => $debit['cost_center_id'] ?? null,
                ]);
            }

            foreach ($credits as $credit) {
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $credit['account_id'],
                    'debit' => 0,
                    'credit' => $credit['amount'],
                    'description' => $credit['description'] ?? null,
                    'cost_center_id' => $credit['cost_center_id'] ?? null,
                ]);
            }

            return $journal;
        });
    }

    public function postTransfer(
        string $sourceAccountCode,
        string $destinationAccountCode,
        float $amount,
        string $referenceType,
        int $referenceId,
        ?int $branchId = null,
        ?string $description = null,
        ?User $user = null
    ): Journal {
        $sourceAccount = $this->findAccountByCode($sourceAccountCode);
        $destinationAccount = $this->findAccountByCode($destinationAccountCode);

        $this->validateJournalBalance([$amount], [$amount]);

        return DB::transaction(function () use ($sourceAccount, $destinationAccount, $amount, $referenceType, $referenceId, $branchId, $description, $user) {
            $journal = Journal::create([
                'reference' => $this->generateReference($referenceType, $referenceId),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'date' => now(),
                'description' => $description ?? "Transfer from {$sourceAccount->name} to {$destinationAccount->name}",
                'status' => JournalStatus::POSTED,
                'branch_id' => $branchId,
                'posted_at' => now(),
                'created_by' => $user?->id,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $destinationAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Transfer to {$destinationAccount->name}",
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $sourceAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Transfer from {$sourceAccount->name}",
            ]);

            return $journal;
        });
    }

    protected function findAccountByCode(string $code): Account
    {
        $account = Account::where('code', $code)->where('is_active', true)->first();

        if (!$account) {
            throw new \InvalidArgumentException("Account with code {$code} not found or inactive");
        }

        return $account;
    }

    protected function generateReference(string $referenceType, int $referenceId): string
    {
        $prefix = strtoupper(Str::snake($referenceType));
        return "{$prefix}-{$referenceId}-" . now()->format('YmdHis');
    }

    /**
     * Validate that journal debits and credits are balanced
     *
     * @param array<float> $debits
     * @param array<float> $credits
     * @throws \InvalidArgumentException
     */
    protected function validateJournalBalance(array $debits, array $credits): void
    {
        $totalDebit = array_sum($debits);
        $totalCredit = array_sum($credits);

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \InvalidArgumentException(
                "Journal is not balanced: Debits ({$totalDebit}) must equal Credits ({$totalCredit})"
            );
        }
    }

    /**
     * Update AR invoice status based on paid amount
     *
     * @param int $arInvoiceId
     */
    protected function updateArInvoiceStatus(int $arInvoiceId): void
    {
        $invoice = ArInvoice::find($arInvoiceId);
        if (!$invoice) {
            return;
        }

        $invoice->updateStatus();
    }

    /**
     * Post enrollment with discount.
     * 
     * Entry:
     * DR Accounts Receivable (net amount after discount)
     * DR Discount Given (discount amount)
     * CR Deferred Revenue (gross amount)
     * 
     * @param float $grossAmount Original amount before discount
     * @param float $discountAmount Total discount applied
     * @param string $referenceType
     * @param int $referenceId
     * @param int|null $branchId
     * @param string|null $description
     * @param User|null $user
     * @return Journal
     */
    public function postEnrollmentWithDiscount(
        float $grossAmount,
        float $discountAmount,
        string $referenceType,
        int $referenceId,
        ?int $branchId = null,
        ?string $description = null,
        ?User $user = null
    ): Journal {
        $netAmount = $grossAmount - $discountAmount;
        
        if ($netAmount < 0) {
            throw new BusinessException('Net amount cannot be negative after discount.');
        }

        $arAccount = $this->findAccountByCode($this->getAccountCode('accounts_receivable', '1130'));
        $deferredRevenue = $this->findAccountByCode($this->getAccountCode('deferred_revenue', '2130'));
        
        $debits = [$netAmount];
        $credits = [$grossAmount];
        
        // Only include discount account if there's a discount
        if ($discountAmount > 0) {
            $discountAccount = $this->findAccountByCodeOrNull($this->getAccountCode('discount_given', '4910'));
            if ($discountAccount) {
                $debits[] = $discountAmount;
            } else {
                // If no discount account, reduce deferred revenue instead
                $credits = [$netAmount];
                Log::warning('Discount account not found, reducing deferred revenue instead', [
                    'discount_amount' => $discountAmount,
                ]);
            }
        }

        $this->validateJournalBalance($debits, $credits);

        return DB::transaction(function () use (
            $arAccount, $deferredRevenue, $grossAmount, $netAmount, $discountAmount,
            $referenceType, $referenceId, $branchId, $description, $user
        ) {
            $journal = Journal::create([
                'reference' => $this->generateReference($referenceType, $referenceId),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'date' => now(),
                'description' => $description ?? "Enrollment created with discount",
                'status' => JournalStatus::POSTED,
                'branch_id' => $branchId,
                'posted_at' => now(),
                'created_by' => $user?->id,
            ]);

            // DR Accounts Receivable (net amount)
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $arAccount->id,
                'debit' => $netAmount,
                'credit' => 0,
                'description' => "Accounts Receivable (net of discount)",
            ]);

            // DR Discount Given (if discount account exists and discount > 0)
            if ($discountAmount > 0) {
                $discountAccount = $this->findAccountByCodeOrNull($this->getAccountCode('discount_given', '4910'));
                if ($discountAccount) {
                    JournalLine::create([
                        'journal_id' => $journal->id,
                        'account_id' => $discountAccount->id,
                        'debit' => $discountAmount,
                        'credit' => 0,
                        'description' => "Discount given",
                    ]);
                }
            }

            // CR Deferred Revenue (gross or net depending on discount account)
            $discountAccount = $this->findAccountByCodeOrNull($this->getAccountCode('discount_given', '4910'));
            $revenueCredit = $discountAccount ? $grossAmount : $netAmount;
            
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $deferredRevenue->id,
                'debit' => 0,
                'credit' => $revenueCredit,
                'description' => "Deferred Revenue",
            ]);

            return $journal;
        });
    }

    /**
     * Reverse a journal entry by creating compensating entries.
     * 
     * @param Journal $originalJournal
     * @param string|null $reason
     * @param User|null $user
     * @return Journal The reversal journal
     */
    public function reverseJournal(
        Journal $originalJournal,
        ?string $reason = null,
        ?User $user = null
    ): Journal {
        // Check if already reversed
        $existingReversal = Journal::where('reference_type', 'reversal')
            ->where('reference_id', $originalJournal->id)
            ->first();

        if ($existingReversal) {
            throw new BusinessException("Journal {$originalJournal->reference} has already been reversed.");
        }

        return DB::transaction(function () use ($originalJournal, $reason, $user) {
            $journal = Journal::create([
                'reference' => $this->generateReference('reversal', $originalJournal->id),
                'reference_type' => 'reversal',
                'reference_id' => $originalJournal->id,
                'date' => now(),
                'description' => $reason ?? "Reversal of {$originalJournal->reference}",
                'status' => JournalStatus::POSTED,
                'branch_id' => $originalJournal->branch_id,
                'posted_at' => now(),
                'created_by' => $user?->id,
            ]);

            // Create reversed lines (swap debits and credits)
            foreach ($originalJournal->journalLines as $line) {
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $line->account_id,
                    'debit' => $line->credit, // Swap
                    'credit' => $line->debit, // Swap
                    'description' => "Reversal: " . ($line->description ?? ''),
                    'cost_center_id' => $line->cost_center_id,
                ]);
            }

            Log::info('Journal reversed', [
                'original_journal_id' => $originalJournal->id,
                'reversal_journal_id' => $journal->id,
                'reason' => $reason,
            ]);

            return $journal;
        });
    }

    /**
     * Cancel an invoice by reversing its journal entries.
     * 
     * @param ArInvoice $invoice
     * @param string|null $reason
     * @param User|null $user
     * @return Journal|null The reversal journal, or null if no journal to reverse
     */
    public function cancelInvoice(
        ArInvoice $invoice,
        ?string $reason = null,
        ?User $user = null
    ): ?Journal {
        // Check if invoice has payments
        $paidAmount = Payment::where('enrollment_id', $invoice->enrollment_id)
            ->where('status', 'paid')
            ->sum('amount');

        if ($paidAmount > 0) {
            throw new BusinessException(
                "Cannot cancel invoice with payments. Please refund payments first."
            );
        }

        // Find the original enrollment journal
        $originalJournal = Journal::where('reference_type', 'enrollment')
            ->where('reference_id', $invoice->enrollment_id)
            ->first();

        if (!$originalJournal) {
            Log::warning('No journal found for invoice cancellation', [
                'invoice_id' => $invoice->id,
                'enrollment_id' => $invoice->enrollment_id,
            ]);
            return null;
        }

        // Reverse the journal
        $reversalJournal = $this->reverseJournal(
            $originalJournal,
            $reason ?? "Invoice #{$invoice->id} cancelled",
            $user
        );

        // Update invoice status
        $invoice->status = 'canceled';
        $invoice->save();

        Log::info('Invoice cancelled', [
            'invoice_id' => $invoice->id,
            'reversal_journal_id' => $reversalJournal->id,
        ]);

        return $reversalJournal;
    }

    /**
     * Reverse a payment by creating compensating entries.
     * 
     * @param Payment $payment
     * @param string|null $reason
     * @param User|null $user
     * @return Journal The reversal journal
     */
    public function reversePayment(
        Payment $payment,
        ?string $reason = null,
        ?User $user = null
    ): Journal {
        // Find the original payment journal
        $originalJournal = Journal::where('reference_type', 'payment')
            ->where('reference_id', $payment->id)
            ->first();

        if (!$originalJournal) {
            throw new BusinessException("No journal entry found for payment #{$payment->id}");
        }

        // Reverse the journal
        $reversalJournal = $this->reverseJournal(
            $originalJournal,
            $reason ?? "Payment #{$payment->id} reversed",
            $user
        );

        // Update payment status
        $payment->status = 'refunded';
        $payment->save();

        // Update invoice status
        if ($payment->enrollment_id) {
            $invoice = ArInvoice::where('enrollment_id', $payment->enrollment_id)->first();
            if ($invoice) {
                $invoice->updateStatus();
            }
        }

        Log::info('Payment reversed', [
            'payment_id' => $payment->id,
            'reversal_journal_id' => $reversalJournal->id,
        ]);

        return $reversalJournal;
    }

    /**
     * Find account by code, returning null if not found (instead of throwing).
     * 
     * @param string $code
     * @return Account|null
     */
    protected function findAccountByCodeOrNull(string $code): ?Account
    {
        return Account::where('code', $code)->where('is_active', true)->first();
    }

    /**
     * Validate that required accounts exist for a transaction type.
     * 
     * @param array $requiredCodes
     * @return array ['valid' => bool, 'missing' => array]
     */
    public function validateAccountsExist(array $requiredCodes): array
    {
        $missing = [];
        
        foreach ($requiredCodes as $code) {
            $account = $this->findAccountByCodeOrNull($code);
            if (!$account) {
                $missing[] = $code;
            }
        }

        return [
            'valid' => empty($missing),
            'missing' => $missing,
        ];
    }

    /**
     * Get account balances for reconciliation.
     * 
     * @param string $accountCode
     * @param \Carbon\Carbon|null $asOfDate
     * @return array
     */
    public function getAccountBalance(string $accountCode, ?\Carbon\Carbon $asOfDate = null): array
    {
        $account = $this->findAccountByCode($accountCode);
        $asOfDate = $asOfDate ?? now();

        $query = JournalLine::where('account_id', $account->id)
            ->whereHas('journal', function ($q) use ($asOfDate) {
                $q->where('status', JournalStatus::POSTED)
                    ->where('date', '<=', $asOfDate);
            });

        $totalDebit = (clone $query)->sum('debit');
        $totalCredit = (clone $query)->sum('credit');
        $openingBalance = (float) $account->opening_balance;

        // Calculate balance based on normal balance
        $balance = $account->normal_balance === 'debit'
            ? $openingBalance + $totalDebit - $totalCredit
            : $openingBalance + $totalCredit - $totalDebit;

        return [
            'account_code' => $accountCode,
            'account_name' => $account->name,
            'opening_balance' => $openingBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'balance' => $balance,
            'as_of_date' => $asOfDate->format('Y-m-d'),
        ];
    }
}

