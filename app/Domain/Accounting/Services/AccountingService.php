<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Models\JournalLine;
use App\Enums\JournalStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountingService
{
    public function postPayment(
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

        return DB::transaction(function () use ($account, $deferredRevenue, $amount, $referenceType, $referenceId, $branchId, $description, $user) {
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
                'account_id' => $deferredRevenue->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Deferred revenue",
            ]);

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

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \InvalidArgumentException('Debit and credit totals must be equal');
        }

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
}

