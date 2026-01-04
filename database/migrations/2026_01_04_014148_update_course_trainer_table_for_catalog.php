<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Try to drop foreign keys - handle if they don't exist
        $this->safeDropForeign('course_trainer', 'course_id');
        $this->safeDropForeign('course_trainer', 'user_id');

        // Drop primary key if it exists
        $this->safeDropPrimary('course_trainer');

        // Drop user_id column if it exists
        if (Schema::hasColumn('course_trainer', 'user_id')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }

        // Add trainer_id column if it doesn't exist
        if (!Schema::hasColumn('course_trainer', 'trainer_id')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->foreignId('trainer_id')->after('course_id')->constrained('users')->cascadeOnDelete();
            });
        }

        // Re-add course_id foreign key if it doesn't exist
        if (!$this->hasForeignKey('course_trainer', 'course_id')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            });
        }

        // Add timestamps if they don't exist
        if (!Schema::hasColumn('course_trainer', 'created_at')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->timestamps();
            });
        }

        // Add unique constraint if it doesn't exist
        if (!$this->hasUniqueConstraint('course_trainer', ['course_id', 'trainer_id'])) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->unique(['course_id', 'trainer_id']);
            });
        }
    }

    public function down(): void
    {
        // Drop unique constraint first (before dropping column)
        if ($this->hasUniqueConstraint('course_trainer', ['course_id', 'trainer_id'])) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->dropUnique(['course_id', 'trainer_id']);
            });
        }
        
        // Drop foreign keys
        $this->safeDropForeign('course_trainer', 'trainer_id');
        $this->safeDropForeign('course_trainer', 'course_id');
        
        // Drop timestamps
        if (Schema::hasColumn('course_trainer', 'created_at')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->dropTimestamps();
            });
        }
        
        // Drop trainer_id column
        if (Schema::hasColumn('course_trainer', 'trainer_id')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->dropColumn('trainer_id');
            });
        }

        // Restore original structure
        Schema::table('course_trainer', function (Blueprint $table) {
            if (!Schema::hasColumn('course_trainer', 'user_id')) {
                $table->foreignId('user_id')->after('course_id')->constrained()->cascadeOnDelete();
            }
            if (!$this->hasForeignKey('course_trainer', 'course_id')) {
                $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            }
            $this->safeDropPrimary('course_trainer');
            $table->primary(['course_id', 'user_id']);
        });
    }

    private function safeDropForeign(string $table, string $column): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            try {
                $constraints = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ", [$table, $column]);

                if (!empty($constraints)) {
                    Schema::table($table, function (Blueprint $table) use ($constraints) {
                        $table->dropForeign($constraints[0]->CONSTRAINT_NAME);
                    });
                }
            } catch (\Exception $e) {
                // Ignore if foreign key doesn't exist or can't be dropped
            }
        } else {
            try {
                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->dropForeign([$column]);
                });
            } catch (\Exception $e) {
                // Ignore if foreign key doesn't exist
            }
        }
    }

    private function safeDropPrimary(string $table): void
    {
        try {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE `{$table}` DROP PRIMARY KEY");
            } else {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropPrimary();
                });
            }
        } catch (\Exception $e) {
            // Ignore if primary key doesn't exist
        }
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            try {
                $constraints = DB::select("
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

    private function hasUniqueConstraint(string $table, array $columns): bool
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            try {
                $indexes = DB::select("SHOW INDEX FROM `{$table}`");
                
                foreach ($indexes as $index) {
                    if ($index->Key_name !== 'PRIMARY' && $index->Non_unique == 0) {
                        $indexColumns = DB::select("
                            SELECT COLUMN_NAME 
                            FROM information_schema.STATISTICS 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = ? 
                            AND INDEX_NAME = ?
                            ORDER BY SEQ_IN_INDEX
                        ", [$table, $index->Key_name]);
                        
                        $indexColumnNames = array_map(fn($col) => $col->COLUMN_NAME, $indexColumns);
                        if (count($indexColumnNames) === count($columns) && 
                            empty(array_diff($indexColumnNames, $columns))) {
                            return true;
                        }
                    }
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }
};
