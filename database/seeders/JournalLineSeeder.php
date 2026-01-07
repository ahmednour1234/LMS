<?php

namespace Database\Seeders;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\CostCenter;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Models\JournalLine;
use Illuminate\Database\Seeder;

class JournalLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $journals = Journal::all();
        $accounts = Account::all();
        $costCenters = CostCenter::all();

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

            // Create 2-5 journal lines per journal
            $linesCount = rand(2, 5);
            $totalDebit = 0;
            $totalCredit = 0;
            $linesToCreate = [];

            for ($i = 1; $i <= $linesCount; $i++) {
                $isDebit = $i <= ($linesCount / 2);
                
                $amount = rand(100, 10000);
                $account = $accounts->random();
                
                $lineData = [
                    'journal_id' => $journal->id,
                    'account_id' => $account->id,
                    'memo' => 'Journal line memo ' . $i,
                ];

                if ($isDebit) {
                    $lineData['debit'] = $amount;
                    $lineData['credit'] = 0;
                    $totalDebit += $amount;
                } else {
                    $lineData['debit'] = 0;
                    $lineData['credit'] = $amount;
                    $totalCredit += $amount;
                }

                // Randomly assign cost center
                if ($costCenters->isNotEmpty() && rand(0, 1) === 1) {
                    $lineData['cost_center_id'] = $costCenters->random()->id;
                }

                $linesToCreate[] = $lineData;
            }

            // Balance the journal (make total debit = total credit)
            if ($totalDebit !== $totalCredit) {
                $difference = abs($totalDebit - $totalCredit);
                $lastIndex = count($linesToCreate) - 1;
                
                if ($lastIndex >= 0) {
                    if ($totalDebit > $totalCredit) {
                        $linesToCreate[$lastIndex]['credit'] += $difference;
                    } else {
                        $linesToCreate[$lastIndex]['debit'] += $difference;
                    }
                }
            }

            // Create all lines
            foreach ($linesToCreate as $lineData) {
                JournalLine::create($lineData);
            }
        }

        $this->command->info('Journal lines seeded successfully!');
    }
}

