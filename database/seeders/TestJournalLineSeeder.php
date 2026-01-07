<?php

namespace Database\Seeders;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Models\JournalLine;
use Illuminate\Database\Seeder;

class TestJournalLineSeeder extends Seeder
{
    /**
     * Run the database seeds for testing.
     * Creates minimal journal lines for testing purposes.
     */
    public function run(): void
    {
        $journals = Journal::take(3)->get();
        $accounts = Account::take(4)->get();

        if ($journals->isEmpty()) {
            $this->command->warn('No journals found. Please seed journals first.');
            return;
        }

        if ($accounts->isEmpty()) {
            $this->command->warn('No accounts found. Please seed accounts first.');
            return;
        }

        foreach ($journals as $journal) {
            // Skip if journal already has lines
            $existingLinesCount = JournalLine::where('journal_id', $journal->id)->count();
            if ($existingLinesCount > 0) {
                continue;
            }

            // Create exactly 2 lines per journal for predictable testing (1 debit, 1 credit)
            $amount = 1000; // Fixed amount for testing
            $debitAccount = $accounts->first();
            $creditAccount = $accounts->skip(1)->first();

            // Debit line
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => 'Test debit line',
            ]);

            // Credit line (balanced)
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => 'Test credit line',
            ]);
        }

        $this->command->info('Test journal lines seeded successfully!');
    }
}

