<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if course_trainer table exists
        if (Schema::hasTable('course_trainer')) {
            // Rename table to course_teacher
            Schema::rename('course_trainer', 'course_teacher');
            
            // Drop existing columns and constraints
            Schema::table('course_teacher', function (Blueprint $table) {
                // Drop foreign keys if they exist
                try {
                    $table->dropForeign(['course_id']);
                } catch (\Exception $e) {
                    // Ignore if doesn't exist
                }
                
                if (Schema::hasColumn('course_teacher', 'trainer_id')) {
                    try {
                        $table->dropForeign(['trainer_id']);
                    } catch (\Exception $e) {
                        // Ignore
                    }
                    $table->dropColumn('trainer_id');
                }
                
                if (Schema::hasColumn('course_teacher', 'user_id')) {
                    try {
                        $table->dropForeign(['user_id']);
                    } catch (\Exception $e) {
                        // Ignore
                    }
                    $table->dropColumn('user_id');
                }

                // Drop primary key if exists
                try {
                    $driver = \DB::getDriverName();
                    if ($driver === 'mysql') {
                        \DB::statement('ALTER TABLE `course_teacher` DROP PRIMARY KEY');
                    } else {
                        $table->dropPrimary();
                    }
                } catch (\Exception $e) {
                    // Ignore if doesn't exist
                }

                // Drop unique constraints if exist
                try {
                    $table->dropUnique(['course_id', 'trainer_id']);
                } catch (\Exception $e) {
                    // Ignore
                }
                try {
                    $table->dropUnique(['course_id', 'user_id']);
                } catch (\Exception $e) {
                    // Ignore
                }

                // Drop timestamps if exist
                if (Schema::hasColumn('course_teacher', 'created_at')) {
                    $table->dropTimestamps();
                }
            });

            // Add new structure
            Schema::table('course_teacher', function (Blueprint $table) {
                // Ensure course_id foreign key exists
                if (!$this->hasForeignKey('course_teacher', 'course_id')) {
                    $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
                }

                // Add teacher_id column
                if (!Schema::hasColumn('course_teacher', 'teacher_id')) {
                    $table->foreignId('teacher_id')->after('course_id')->constrained('teachers')->cascadeOnDelete();
                }

                // Add primary key
                $table->primary(['course_id', 'teacher_id']);
                
                // Add index
                $table->index('teacher_id');
            });
        } else {
            // Create new course_teacher table if it doesn't exist
            if (!Schema::hasTable('course_teacher')) {
                Schema::create('course_teacher', function (Blueprint $table) {
                    $table->foreignId('course_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                    $table->primary(['course_id', 'teacher_id']);
                    $table->index('teacher_id');
                });
            }
        }
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        $driver = \DB::getDriverName();
        
        if ($driver === 'mysql') {
            try {
                $constraints = \DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ", [$table, $column]);

                return !empty($constraints);
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only rollback if course_teacher table exists
        if (Schema::hasTable('course_teacher')) {
            // Drop foreign keys and constraints before renaming
            Schema::table('course_teacher', function (Blueprint $table) {
                // Drop foreign keys
                try {
                    $table->dropForeign(['teacher_id']);
                } catch (\Exception $e) {
                    // Ignore if doesn't exist
                }
                try {
                    $table->dropForeign(['course_id']);
                } catch (\Exception $e) {
                    // Ignore if doesn't exist
                }
                
                // Drop primary key
                try {
                    $driver = \DB::getDriverName();
                    if ($driver === 'mysql') {
                        \DB::statement('ALTER TABLE `course_teacher` DROP PRIMARY KEY');
                    } else {
                        $table->dropPrimary();
                    }
                } catch (\Exception $e) {
                    // Ignore if doesn't exist
                }
                
                // Drop teacher_id column
                if (Schema::hasColumn('course_teacher', 'teacher_id')) {
                    $table->dropColumn('teacher_id');
                }
            });
            
            // Rename table back to course_trainer
            Schema::rename('course_teacher', 'course_trainer');
            
            // Restore original structure (user_id with foreign keys)
            Schema::table('course_trainer', function (Blueprint $table) {
                // Add user_id if it doesn't exist
                if (!Schema::hasColumn('course_trainer', 'user_id')) {
                    $table->foreignId('user_id')->after('course_id')->constrained()->cascadeOnDelete();
                }
                
                // Ensure course_id foreign key exists
                if (!$this->hasForeignKey('course_trainer', 'course_id')) {
                    $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
                }
                
                // Recreate primary key
                try {
                    $table->primary(['course_id', 'user_id']);
                } catch (\Exception $e) {
                    // Ignore if primary key already exists
                }
            });
        }
    }
};
