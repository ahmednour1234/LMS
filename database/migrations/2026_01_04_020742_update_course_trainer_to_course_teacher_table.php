<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ---------- helpers ----------
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

    private function dropPrimaryIfExists(string $table): void
    {
        // MySQL: DROP PRIMARY KEY fails if none, so catch
        try {
            DB::statement("ALTER TABLE `$table` DROP PRIMARY KEY");
        } catch (\Throwable $e) {
            // ignore
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

    // ---------- migration ----------
    public function up(): void
    {
        // Determine source table
        $hasTrainer = Schema::hasTable('course_trainer');
        $hasTeacher = Schema::hasTable('course_teacher');

        // If both exist, we will keep course_teacher as the target and ignore old one (safe)
        if ($hasTrainer && !$hasTeacher) {
            Schema::rename('course_trainer', 'course_teacher');
            $hasTeacher = true;
        }

        // If still not exists, create fresh
        if (!$hasTeacher) {
            Schema::create('course_teacher', function (Blueprint $table) {
                $table->unsignedBigInteger('course_id');
                $table->unsignedBigInteger('teacher_id');

                $table->primary(['course_id', 'teacher_id'], 'course_teacher_pk');
                $table->index('teacher_id', 'course_teacher_teacher_idx');

                $table->foreign('course_id', 'course_teacher_course_fk')
                    ->references('id')->on('courses')
                    ->cascadeOnDelete();

                $table->foreign('teacher_id', 'course_teacher_teacher_fk')
                    ->references('id')->on('teachers')
                    ->cascadeOnDelete();
            });

            return;
        }

        /**
         * Now: course_teacher exists.
         * We need to normalize it to:
         * (course_id, teacher_id) PK, FK -> courses & teachers, plus teacher_id index.
         */

        // 1) Drop any FKs that might exist on these columns (whatever their names)
        foreach (['course_id', 'trainer_id', 'user_id', 'teacher_id'] as $col) {
            if (Schema::hasColumn('course_teacher', $col)) {
                $this->dropAllForeignKeysForColumn('course_teacher', $col);
            }
        }

        // 2) Drop primary key if exists (so we can recreate it)
        $this->dropPrimaryIfExists('course_teacher');

        // 3) Drop old unique/index leftovers if exist (common names)
        $this->dropIndexIfExists('course_teacher', 'course_teacher_course_id_trainer_id_unique');
        $this->dropIndexIfExists('course_teacher', 'course_teacher_course_id_user_id_unique');
        $this->dropIndexIfExists('course_teacher', 'course_trainer_course_id_user_id_unique');
        $this->dropIndexIfExists('course_teacher', 'course_teacher_teacher_id_index');
        $this->dropIndexIfExists('course_teacher', 'course_teacher_user_id_index');

        // 4) Remove irrelevant columns if they exist
        Schema::table('course_teacher', function (Blueprint $table) {
            // Remove timestamps if exist (pivot style)
            if (Schema::hasColumn('course_teacher', 'created_at') || Schema::hasColumn('course_teacher', 'updated_at')) {
                try { $table->dropTimestamps(); } catch (\Throwable $e) {}
            }

            if (Schema::hasColumn('course_teacher', 'trainer_id')) {
                $table->dropColumn('trainer_id');
            }

            if (Schema::hasColumn('course_teacher', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });

        // 5) Ensure teacher_id exists
        if (!Schema::hasColumn('course_teacher', 'teacher_id')) {
            Schema::table('course_teacher', function (Blueprint $table) {
                $table->unsignedBigInteger('teacher_id')->after('course_id');
            });
        }

        // 6) Ensure indexes / PK
        // index on teacher_id
        if (!$this->indexExists('course_teacher', 'course_teacher_teacher_idx')) {
            Schema::table('course_teacher', function (Blueprint $table) {
                $table->index('teacher_id', 'course_teacher_teacher_idx');
            });
        }

        // PK composite
        // Note: PK creation will automatically create an index, so do it after dropping old PK
        Schema::table('course_teacher', function (Blueprint $table) {
            // avoid exception if already exists
            try {
                $table->primary(['course_id', 'teacher_id'], 'course_teacher_pk');
            } catch (\Throwable $e) {
                // ignore
            }
        });

        // 7) Recreate FKs with fixed names (avoid errno 121 duplicates)
        // Drop if exist by name first (safe)
        $this->dropAllForeignKeysForColumn('course_teacher', 'course_id');
        $this->dropAllForeignKeysForColumn('course_teacher', 'teacher_id');

        // Create with fixed names (if tables exist)
        if (Schema::hasTable('courses')) {
            try {
                DB::statement("
                    ALTER TABLE `course_teacher`
                    ADD CONSTRAINT `course_teacher_course_fk`
                    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`)
                    ON DELETE CASCADE
                ");
            } catch (\Throwable $e) {
                // ignore if already exists
            }
        }

        if (Schema::hasTable('teachers')) {
            try {
                DB::statement("
                    ALTER TABLE `course_teacher`
                    ADD CONSTRAINT `course_teacher_teacher_fk`
                    FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`)
                    ON DELETE CASCADE
                ");
            } catch (\Throwable $e) {
                // ignore if already exists
            }
        }
    }

    public function down(): void
    {
        // Rollback best-effort (safe)
        if (!Schema::hasTable('course_teacher')) {
            return;
        }

        // Drop FKs (any names)
        foreach (['course_id', 'teacher_id'] as $col) {
            if (Schema::hasColumn('course_teacher', $col)) {
                $this->dropAllForeignKeysForColumn('course_teacher', $col);
            }
        }

        // Drop PK
        $this->dropPrimaryIfExists('course_teacher');

        // Drop teacher_id index (if exists)
        $this->dropIndexIfExists('course_teacher', 'course_teacher_teacher_idx');

        // Rename back only if course_trainer doesn't exist
        if (!Schema::hasTable('course_trainer')) {
            Schema::rename('course_teacher', 'course_trainer');
        }

        // Restore old structure with user_id pivot (best-effort)
        if (Schema::hasTable('course_trainer')) {
            if (!Schema::hasColumn('course_trainer', 'user_id')) {
                Schema::table('course_trainer', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->after('course_id');
                });
            }

            // recreate PK
            Schema::table('course_trainer', function (Blueprint $table) {
                try {
                    $table->primary(['course_id', 'user_id'], 'course_trainer_pk');
                } catch (\Throwable $e) {}
            });

            // recreate FKs with fixed names
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
        }
    }
};
