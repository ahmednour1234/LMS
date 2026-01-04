<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ---------- helpers ----------
    private function dropPrimaryIfExists(string $table): void
    {
        try {
            DB::statement("ALTER TABLE `$table` DROP PRIMARY KEY");
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function fkNamesForColumn(string $table, string $column): array
    {
        $db = DB::getDatabaseName();

        $rows = DB::select("
            SELECT CONSTRAINT_NAME as name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$db, $table, $column]);

        return array_values(array_unique(array_map(fn($r) => $r->name, $rows)));
    }

    private function dropAllForeignKeysForColumn(string $table, string $column): void
    {
        foreach ($this->fkNamesForColumn($table, $column) as $fkName) {
            DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$fkName`");
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();

        $row = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = ?
              AND table_name   = ?
              AND index_name   = ?
            LIMIT 1
        ", [$db, $table, $indexName]);

        return (bool) $row;
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            DB::statement("ALTER TABLE `$table` DROP INDEX `$indexName`");
        }
    }

    public function up(): void
    {
        if (!Schema::hasTable('course_trainer')) {
            return;
        }

        /**
         * 1) Drop FKs on course_id / user_id (any names)
         */
        if (Schema::hasColumn('course_trainer', 'course_id')) {
            $this->dropAllForeignKeysForColumn('course_trainer', 'course_id');
        }
        if (Schema::hasColumn('course_trainer', 'user_id')) {
            $this->dropAllForeignKeysForColumn('course_trainer', 'user_id');
        }

        /**
         * 2) Drop PRIMARY first (this prevents 1068 in any later changes)
         */
        $this->dropPrimaryIfExists('course_trainer');

        /**
         * 3) Drop old unique (if any) to avoid conflicts
         */
        $this->dropIndexIfExists('course_trainer', 'course_trainer_course_id_user_id_unique');
        $this->dropIndexIfExists('course_trainer', 'course_id'); // sometimes indexes named by column
        $this->dropIndexIfExists('course_trainer', 'user_id');

        /**
         * 4) Ensure trainer_id exists
         */
        if (!Schema::hasColumn('course_trainer', 'trainer_id')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->unsignedBigInteger('trainer_id')->after('course_id');
            });
        }

        /**
         * 5) Drop user_id column (after dropping pk/fk)
         */
        if (Schema::hasColumn('course_trainer', 'user_id')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }

        /**
         * 6) Add timestamps if missing
         */
        if (!Schema::hasColumn('course_trainer', 'created_at')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->timestamps();
            });
        }

        /**
         * 7) Recreate constraints with FIXED names (no guesswork)
         * - Unique instead of PK (recommended for pivot tables with timestamps)
         *   If you really want PK composite, we can do it too, but unique is safer with timestamps.
         */
        // FK course_id
        try {
            DB::statement("
                ALTER TABLE `course_trainer`
                ADD CONSTRAINT `course_trainer_course_fk`
                FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`)
                ON DELETE CASCADE
            ");
        } catch (\Throwable $e) {}

        // FK trainer_id -> users.id
        try {
            DB::statement("
                ALTER TABLE `course_trainer`
                ADD CONSTRAINT `course_trainer_trainer_fk`
                FOREIGN KEY (`trainer_id`) REFERENCES `users`(`id`)
                ON DELETE CASCADE
            ");
        } catch (\Throwable $e) {}

        // Unique(course_id, trainer_id)
        if (!$this->indexExists('course_trainer', 'course_trainer_course_trainer_unique')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->unique(['course_id', 'trainer_id'], 'course_trainer_course_trainer_unique');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('course_trainer')) {
            return;
        }

        /**
         * 1) Drop FKs first (any names)
         */
        foreach (['course_id', 'trainer_id', 'user_id'] as $col) {
            if (Schema::hasColumn('course_trainer', $col)) {
                $this->dropAllForeignKeysForColumn('course_trainer', $col);
            }
        }

        /**
         * 2) Drop UNIQUE first (if exists)
         */
        $this->dropIndexIfExists('course_trainer', 'course_trainer_course_trainer_unique');

        /**
         * 3) Drop PRIMARY if exists (very important to avoid 1068)
         */
        $this->dropPrimaryIfExists('course_trainer');

        /**
         * 4) Remove timestamps if exist
         */
        if (Schema::hasColumn('course_trainer', 'created_at')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                try { $table->dropTimestamps(); } catch (\Throwable $e) {}
            });
        }

        /**
         * 5) Ensure user_id exists back
         */
        if (!Schema::hasColumn('course_trainer', 'user_id')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->after('course_id');
            });
        }

        /**
         * 6) Drop trainer_id column
         */
        if (Schema::hasColumn('course_trainer', 'trainer_id')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->dropColumn('trainer_id');
            });
        }

        /**
         * 7) Restore FKs with fixed names
         */
        try {
            DB::statement("
                ALTER TABLE `course_trainer`
                ADD CONSTRAINT `course_trainer_course_fk`
                FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`)
                ON DELETE CASCADE
            ");
        } catch (\Throwable $e) {}

        try {
            DB::statement("
                ALTER TABLE `course_trainer`
                ADD CONSTRAINT `course_trainer_user_fk`
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
                ON DELETE CASCADE
            ");
        } catch (\Throwable $e) {}

        /**
         * 8) Restore PK (only AFTER dropping any existing PK)
         * لو عايز PK مركب:
         */
        Schema::table('course_trainer', function (Blueprint $table) {
            try {
                $table->primary(['course_id', 'user_id'], 'course_trainer_pk');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};
