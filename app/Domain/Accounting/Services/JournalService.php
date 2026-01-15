<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Models\JournalLine;
use App\Domain\Accounting\Models\Payment;
use App\Enums\JournalStatus;
use App\Exceptions\BusinessException;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JournalService
{
    public function createForPayment(Payment $payment): Journal
    {
        if ($payment->journal()->exists()) {
            return $payment->journal()->first();
        }

        if ($payment->status !== 'paid') {
            throw new BusinessException('Journal can only be created for paid payments.');
        }

        return DB::transaction(function () use ($payment) {
            $cashOrBankAccount = $this->resolveCashOrBankAccount($payment);
            $revenueOrExpenseAccount = $this->resolveRevenueOrExpenseAccount($payment);

            $amount = (float) $payment->amount;

            $journal = Journal::create([
                'reference' => $this->generateReference($payment),
                'reference_type' => 'payment',
                'reference_id' => $payment->id,
                'journal_date' => $payment->paid_at ?? $payment->created_at,
                'description' => $this->generateDescription($payment),
                'status' => JournalStatus::DRAFT,
                'branch_id' => $payment->branch_id,
                'created_by' => $payment->created_by,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $cashOrBankAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => 'Payment received',
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $revenueOrExpenseAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => 'Revenue earned',
            ]);

            $this->validateJournalBalanced($journal);

            return $journal;
        });
    }

    protected function getAccountCodeFromSetting(string $settingKey): string
    {
        $setting = Setting::where('key', $settingKey)->first();

        if (!$setting) {
            throw new BusinessException("Setting '{$settingKey}' not found. Please configure default account codes in system settings.");
        }

        $value = $setting->value;
        if (!is_array($value) || !isset($value['code'])) {
            throw new BusinessException("Setting '{$settingKey}' does not contain a valid account code.");
        }

        return $value['code'];
    }

    protected function resolveAccountByCode(string $code): Account
    {
        $account = Account::where('code', $code)->where('is_active', true)->first();

        if (!$account) {
            throw new BusinessException("Account with code '{$code}' not found or inactive. Please check account configuration.");
        }

        return $account;
    }

    protected function resolveCashOrBankAccount(Payment $payment): Account
    {
        $settingKey = match ($payment->method) {
            'cash' => 'default_cash_account_code',
            'bank', 'gateway' => 'default_bank_account_code',
            default => throw new BusinessException("Unknown payment method: {$payment->method}"),
        };

        $accountCode = $this->getAccountCodeFromSetting($settingKey);
        return $this->resolveAccountByCode($accountCode);
    }

    protected function resolveRevenueOrExpenseAccount(Payment $payment): Account
    {
        if ($payment->enrollment_id) {
            $accountCode = $this->getAccountCodeFromSetting('default_revenue_account_code');
            return $this->resolveAccountByCode($accountCode);
        }

        $accountCode = $this->getAccountCodeFromSetting('default_expense_account_code');
        return $this->resolveAccountByCode($accountCode);
    }

    protected function validateJournalBalanced(Journal $journal): void
    {
        $journal->load('journalLines');
        
        $totalDebit = $journal->journalLines->sum('debit');
        $totalCredit = $journal->journalLines->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new BusinessException(
                "Journal is not balanced: Debits ({$totalDebit}) must equal Credits ({$totalCredit})"
            );
        }
    }

    protected function generateReference(Payment $payment): string
    {
        return "PAYMENT-{$payment->id}-" . now()->format('YmdHis');
    }

    protected function generateDescription(Payment $payment): string
    {
        $methodName = match ($payment->method) {
            'cash' => 'Cash',
            'bank' => 'Bank Transfer',
            'gateway' => 'Payment Gateway',
            default => ucfirst($payment->method),
        };

        return "Payment received via {$methodName}";
    }
}
