<?php

namespace Database\Seeders;

use App\Domain\Accounting\Models\Journal;
use App\Domain\Branch\Models\Branch;
use App\Enums\JournalStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class JournalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        $users = User::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Please seed branches first.');
            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please seed users first.');
            return;
        }

        // Create 5-10 journals per branch
        foreach ($branches as $branch) {
            $journalCount = rand(5, 10);
            
            for ($i = 1; $i <= $journalCount; $i++) {
                $statuses = [JournalStatus::DRAFT, JournalStatus::POSTED];
                $status = $statuses[array_rand($statuses)];
                
                $reference = 'JRN-' . $branch->id . '-' . str_pad($i, 4, '0', STR_PAD_LEFT) . '-' . date('Y');

                $journalData = [
                    'reference' => $reference,
                    'journal_date' => now()->subDays(rand(0, 90)),
                    'description' => 'Journal entry ' . $i,
                    'status' => $status,
                    'branch_id' => $branch->id,
                    'created_by' => $users->random()->id,
                    'updated_by' => $users->random()->id,
                ];

                if ($status === JournalStatus::POSTED) {
                    $journalData['posted_at'] = now()->subDays(rand(0, 30));
                }

                // Use create with check to avoid issues with original_status attribute
                $existingJournal = Journal::where('reference', $reference)->first();
                if (!$existingJournal) {
                    Journal::create($journalData);
                }
            }
        }

        $this->command->info('Journals seeded successfully!');
    }
}

