<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Models\JournalLine;
use App\Domain\Accounting\Models\Voucher;
use App\Enums\JournalStatus;
use App\Enums\VoucherStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VoucherPostingService
{
    public function post(Voucher $voucher, User $user): void
    {
        if (!$voucher->isDraft()) {
            throw new \RuntimeException('Only draft vouchers can be posted.');
        }

        if (!$voucher->isBalanced()) {
            throw new \RuntimeException('Voucher is not balanced. Debit total must equal Credit total.');
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

            foreach ($voucher->voucherLines as $line) {
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $line->account_id,
                    'cost_center_id' => $line->cost_center_id,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'memo' => $line->description,
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
