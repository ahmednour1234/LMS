<?php

namespace Database\Seeders;

use App\Domain\Branch\Models\Branch;
use App\Domain\Media\Models\MediaFile;
use App\Models\User;
use Illuminate\Database\Seeder;

class MediaFileSeeder extends Seeder
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

        $mimeTypes = [
            'video/mp4' => ['mp4'],
            'application/pdf' => ['pdf'],
            'image/jpeg' => ['jpg'],
            'image/png' => ['png'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        ];

        foreach ($branches as $branch) {
            $filesCount = rand(10, 20);
            
            for ($i = 1; $i <= $filesCount; $i++) {
                $mimeType = array_rand($mimeTypes);
                $extension = $mimeTypes[$mimeType][0];
                $filename = 'file_' . $branch->id . '_' . $i . '.' . $extension;
                
                $fileData = [
                    'filename' => $filename,
                    'original_filename' => 'original_' . $filename,
                    'mime_type' => $mimeType,
                    'size' => rand(1000, 50000000), // 1KB to 50MB
                    'disk' => 'local',
                    'path' => 'media/' . $branch->id . '/' . $filename,
                    'user_id' => $users->random()->id,
                    'branch_id' => $branch->id,
                    'is_private' => rand(0, 1) === 1,
                ];

                MediaFile::create($fileData);
            }
        }

        $this->command->info('Media files seeded successfully!');
    }
}

