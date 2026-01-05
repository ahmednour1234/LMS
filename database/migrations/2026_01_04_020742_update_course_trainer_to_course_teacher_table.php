<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ---------- low-level helpers (information_schema) ----------
    private function dbName(): string
    {
        return DB::getDatabaseName();
    }

    private function columnExists(string $table, string $column): bool
    {
        $db = $this->dbName();

        $row = DB::selectOne("
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1
        ", [$db, $table, $column]);

        return (bool) $row;
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        if ($this->columnExists($table, $column)) {
            DB::statement("ALTER TABLE `$table` DROP COLUMN `$column`");
        }
    }

    private function fkNamesForColumn(string $table, string $column): array
    {
        $db = $this->dbName();

        $rows = DB::select("
            SELECT CONSTRAINT_NAME as name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$db, $table, $column]);

        return array_values(array_unique(array_map(fn ($r) => $r->name, $rows)));
    }

    private function dropAllForeignKeysForColumn(string $table, string $column): void
    {
        foreach ($this->fkNamesForColumn($table, $column) as $fkName) {
            try {
                DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$fkName`");
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    private function dropPrimaryIfExists(string $table): void
    {
        try {
            DB::statement("ALTER TABLE `$table` DROP PRIMARY KEY");
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = $this->dbName();

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
            try {
                DB::statement("ALTER TABLE `$table` DROP INDEX `$indexName`");
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    // ---------- migration ----------
    public function up(): void
    {
        // Determine source
        $hasTrainer = Schema::hasTable('course_trainer');
        $hasTeacher = Schema::hasTable('course_teacher');

        // Rename old -> new if needed
        if ($hasTrainer && !$hasTeacher) {
            Schema::rename('course_trainer', 'course_teacher');
            $hasTeacher = true;
        }

        // If not exists create fresh
        if (!$hasTeacher) {
            Schema::create('course_teacher', function (Blueprint $table) {
                $table->engine = 'InnoDB';

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

        // 1) Drop any FKs on legacy columns (only if column exists)
        foreach (['course_id', 'teacher_id', 'trainer_id', 'user_id'] as $col) {
            if ($this->columnExists('course_teacher', $col)) {
                $this->dropAllForeignKeysForColumn('course_teacher', $col);
            }
        }

        // 2) Drop PK and common indexes
        $this->dropPrimaryIfExists('course_teacher');

        foreach ([
            'course_teacher_course_id_trainer_id_unique',
            'course_teacher_course_id_user_id_unique',
            'course_trainer_course_id_user_id_unique',
            'course_teacher_teacher_id_index',
            'course_teacher_user_id_index',
            'course_teacher_teacher_idx',
        ] as $idx) {
            $this->dropIndexIfExists('course_teacher', $idx);
        }

        // 3) Drop legacy columns safely using information_schema (NO Schema::dropColumn)
        $this->dropColumnIfExists('course_teacher', 'trainer_id');
        $this->dropColumnIfExists('course_teacher', 'user_id');

        // Drop timestamps safely (each one separately)
        $this->dropColumnIfExists('course_teacher', 'created_at');
        $this->dropColumnIfExists('course_teacher', 'updated_at');

        // 4) Ensure teacher_id exists
        if (!$this->columnExists('course_teacher', 'teacher_id')) {
            Schema::table('course_teacher', function (Blueprint $table) {
                $table->unsignedBigInteger('teacher_id')->after('course_id');
            });
        }

        // 5) Ensure index on teacher_id
        if (!$this->indexExists('course_teacher', 'course_teacher_teacher_idx')) {
            Schema::table('course_teacher', function (Blueprint $table) {
                $table->index('teacher_id', 'course_teacher_teacher_idx');
            });
        }

        // 6) Ensure composite PK
        Schema::table('course_teacher', function (Blueprint $table) {
            try {
                $table->primary(['course_id', 'teacher_id'], 'course_teacher_pk');
            } catch (\Throwable $e) {
                // ignore
            }
        });

        // 7) Recreate FKs with fixed names
        $this->dropAllForeignKeysForColumn('course_teacher', 'course_id');
        $this->dropAllForeignKeysForColumn('course_teacher', 'teacher_id');

        if (Schema::hasTable('courses')) {
            try {
                DB::statement("
                    ALTER TABLE `course_teacher`
                    ADD CONSTRAINT `course_teacher_course_fk`
                    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`)
                    ON DELETE CASCADE
                ");
            } catch (\Throwable $e) {
                // ignore
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
                // ignore
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('course_teacher')) {
            return;
        }

        // drop FKs
        foreach (['course_id', 'teacher_id'] as $col) {
            if ($this->columnExists('course_teacher', $col)) {
                $this->dropAllForeignKeysForColumn('course_teacher', $col);
            }
        }

        // drop PK + index
        $this->dropPrimaryIfExists('course_teacher');
        $this->dropIndexIfExists('course_teacher', 'course_teacher_teacher_idx');

        // rename back if course_trainer doesn't exist
        if (!Schema::hasTable('course_trainer')) {
            Schema::rename('course_teacher', 'course_trainer');
        }

        // best-effort restore old structure
        if (Schema::hasTable('course_trainer')) {
            if (!$this->columnExists('course_trainer', 'user_id')) {
                Schema::table('course_trainer', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->after('course_id');
                });
            }

            Schema::table('course_trainer', function (Blueprint $table) {
                try {
                    $table->primary(['course_id', 'user_id'], 'course_trainer_pk');
                } catch (\Throwable $e) {
                    // ignore
                }
            });

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
