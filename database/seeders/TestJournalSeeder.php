<?php

namespace Database\Seeders;

use App\Domain\Accounting\Models\Journal;
use App\Domain\Branch\Models\Branch;
use App\Enums\JournalStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestJournalSeeder extends Seeder
{
    /**
     * Run the database seeds for testing.
     * Creates minimal journals for testing purposes.
     */
    public function run(): void
    {
        $branch = Branch::first();
        $user = User::first();

        if (!$branch) {
            $this->command->warn('No branches found. Please seed branches first.');
            return;
        }

        if (!$user) {
            $this->command->warn('No users found. Please seed users first.');
            return;
        }

        // Create exactly 2 journals for predictable testing
        for ($i = 1; $i <= 2; $i++) {
            $reference = 'TEST-JRN-' . str_pad($i, 4, '0', STR_PAD_LEFT) . '-' . date('Y');
            
            // First journal is DRAFT, second is POSTED
            $status = $i === 1 ? JournalStatus::DRAFT : JournalStatus::POSTED;

            $journalData = [
                'reference' => $reference,
                'journal_date' => now()->subDays($i),
                'description' => "Test Journal Entry {$i}",
                'status' => $status,
                'branch_id' => $branch->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ];

            if ($status === JournalStatus::POSTED) {
                $journalData['posted_at'] = now()->subDays($i - 1);
            }

            // Use create with check to avoid issues with original_status attribute
            $existingJournal = Journal::where('reference', $reference)->first();
            if (!$existingJournal) {
                Journal::create($journalData);
            }
        }

        $this->command->info('Test journals seeded successfully!');
    }
}

