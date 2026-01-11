<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Models\JournalLine;
use App\Domain\Accounting\Models\Voucher;
use App\Enums\JournalStatus;
use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VoucherPostingService
{
    public function post(Voucher $voucher, User $user): void
    {
        if (!$voucher->isDraft()) {
            throw new \RuntimeException('Only draft vouchers can be posted.');
        }

        if (!$voucher->cash_bank_account_id || !$voucher->counterparty_account_id) {
            throw new \RuntimeException('Cash/Bank account and Counterparty account are required.');
        }

        if (!$voucher->amount || $voucher->amount <= 0) {
            throw new \RuntimeException('Amount must be greater than zero.');
        }

        DB::transaction(function () use ($voucher, $user) {
            $journal = Journal::create([
                'reference' => $voucher->voucher_no,
                'reference_type' => 'voucher',
                'reference_id' => $voucher->id,
                'voucher_id' => $voucher->id,
                'journal_date' => $voucher->voucher_date,
                'description' => $voucher->description ?? "Voucher {$voucher->voucher_no}",
                'status' => JournalStatus::POSTED,
                'branch_id' => $voucher->branch_id,
                'posted_at' => now(),
                'posted_by' => $user->id,
                'created_by' => $user->id,
            ]);

            $amount = $voucher->amount;
            $memo = $voucher->line_description ?? $voucher->description;

            if ($voucher->voucher_type === VoucherType::RECEIPT) {
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $voucher->cash_bank_account_id,
                    'cost_center_id' => $voucher->cost_center_id,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => $memo,
                ]);

                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $voucher->counterparty_account_id,
                    'cost_center_id' => $voucher->cost_center_id,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => $memo,
                ]);
            } else {
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $voucher->cash_bank_account_id,
                    'cost_center_id' => $voucher->cost_center_id,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => $memo,
                ]);

                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $voucher->counterparty_account_id,
                    'cost_center_id' => $voucher->cost_center_id,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => $memo,
                ]);
            }

            $voucher->update([
                'status' => VoucherStatus::POSTED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        });
    }

    public function cancel(Voucher $voucher, User $user): void
    {
        if (!$voucher->isPosted()) {
            throw new \RuntimeException('Only posted vouchers can be cancelled.');
        }

        DB::transaction(function () use ($voucher, $user) {
            $journal = Journal::where('voucher_id', $voucher->id)
                ->where('status', JournalStatus::POSTED)
                ->first();

            if ($journal) {
                $journal->update(['status' => JournalStatus::VOID]);
            }

            $voucher->update([
                'status' => VoucherStatus::CANCELLED,
            ]);
        });
    }
}
