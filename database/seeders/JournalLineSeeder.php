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

            // Create 2-5 journal lines per journal (ensure even number for balancing)
            $linesCount = max(2, rand(2, 5));
            $totalDebit = 0;
            $totalCredit = 0;
            $linesToCreate = [];

            // Ensure we have at least one debit and one credit line
            $debitCount = max(1, (int) floor($linesCount / 2));
            $creditCount = max(1, $linesCount - $debitCount);

            for ($i = 1; $i <= $linesCount; $i++) {
                $isDebit = $i <= $debitCount;
                
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
            if ($totalDebit !== $totalCredit && count($linesToCreate) > 0) {
                $difference = abs($totalDebit - $totalCredit);
                $lastIndex = count($linesToCreate) - 1;
                
                if ($totalDebit > $totalCredit) {
                    // Need more credit - add to last credit line or create new
                    $lastCreditIndex = $lastIndex;
                    while ($lastCreditIndex >= 0 && $linesToCreate[$lastCreditIndex]['debit'] > 0) {
                        $lastCreditIndex--;
                    }
                    if ($lastCreditIndex >= 0) {
                        $linesToCreate[$lastCreditIndex]['credit'] += $difference;
                    } else {
                        // If no credit line found, add to last line
                        $linesToCreate[$lastIndex]['credit'] += $difference;
                        $linesToCreate[$lastIndex]['debit'] = max(0, $linesToCreate[$lastIndex]['debit'] - $difference);
                    }
                } else {
                    // Need more debit - add to last debit line or create new
                    $lastDebitIndex = $lastIndex;
                    while ($lastDebitIndex >= 0 && $linesToCreate[$lastDebitIndex]['credit'] > 0) {
                        $lastDebitIndex--;
                    }
                    if ($lastDebitIndex >= 0) {
                        $linesToCreate[$lastDebitIndex]['debit'] += $difference;
                    } else {
                        // If no debit line found, add to last line
                        $linesToCreate[$lastIndex]['debit'] += $difference;
                        $linesToCreate[$lastIndex]['credit'] = max(0, $linesToCreate[$lastIndex]['credit'] - $difference);
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

