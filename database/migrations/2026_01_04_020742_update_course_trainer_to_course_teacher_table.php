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
            try {
                DB::statement("ALTER TABLE `$table` DROP INDEX `$indexName`");
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function up(): void
    {
        // ---------- determine source ----------
        $hasTrainer = Schema::hasTable('course_trainer');
        $hasTeacher = Schema::hasTable('course_teacher');

        // If only old exists -> rename to new
        if ($hasTrainer && !$hasTeacher) {
            Schema::rename('course_trainer', 'course_teacher');
            $hasTeacher = true;
        }

        // If still not exists -> create fresh target
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

        // ---------- normalize existing course_teacher ----------
        // Snapshot columns OUTSIDE Schema::table closure (important!)
        $columns = [];
        try {
            $columns = Schema::getColumnListing('course_teacher');
        } catch (\Throwable $e) {
            $columns = [];
        }

        $hasCol = fn (string $c) => in_array($c, $columns, true);

        // 1) Drop any FKs on possible legacy columns
        foreach (['course_id', 'teacher_id', 'trainer_id', 'user_id'] as $col) {
            if ($hasCol($col)) {
                $this->dropAllForeignKeysForColumn('course_teacher', $col);
            }
        }

        // 2) Drop PK + common leftover indexes
        $this->dropPrimaryIfExists('course_teacher');

        $this->dropIndexIfExists('course_teacher', 'course_teacher_course_id_trainer_id_unique');
        $this->dropIndexIfExists('course_teacher', 'course_teacher_course_id_user_id_unique');
        $this->dropIndexIfExists('course_teacher', 'course_trainer_course_id_user_id_unique');
        $this->dropIndexIfExists('course_teacher', 'course_teacher_teacher_id_index');
        $this->dropIndexIfExists('course_teacher', 'course_teacher_user_id_index');
        $this->dropIndexIfExists('course_teacher', 'course_teacher_teacher_idx');

        // 3) Apply column drops/adds safely
        $dropColumns = [];
        if ($hasCol('trainer_id')) $dropColumns[] = 'trainer_id';
        if ($hasCol('user_id'))    $dropColumns[] = 'user_id';

        // timestamps: drop individually if exist
        $dropTimestamps = [];
        if ($hasCol('created_at')) $dropTimestamps[] = 'created_at';
        if ($hasCol('updated_at')) $dropTimestamps[] = 'updated_at';

        // Add teacher_id if missing
        if (!$hasCol('teacher_id')) {
            Schema::table('course_teacher', function (Blueprint $table) {
                $table->unsignedBigInteger('teacher_id')->after('course_id');
            });
            // refresh columns snapshot
            $columns = Schema::getColumnListing('course_teacher');
            $hasCol = fn (string $c) => in_array($c, $columns, true);
        }

        // Execute drops in ONE table call (only if needed)
        if (!empty($dropColumns) || !empty($dropTimestamps)) {
            Schema::table('course_teacher', function (Blueprint $table) use ($dropColumns, $dropTimestamps) {
                foreach ($dropTimestamps as $c) {
                    try { $table->dropColumn($c); } catch (\Throwable $e) {}
                }
                foreach ($dropColumns as $c) {
                    try { $table->dropColumn($c); } catch (\Throwable $e) {}
                }
            });
        }

        // 4) Ensure index on teacher_id
        if (!$this->indexExists('course_teacher', 'course_teacher_teacher_idx')) {
            Schema::table('course_teacher', function (Blueprint $table) {
                $table->index('teacher_id', 'course_teacher_teacher_idx');
            });
        }

        // 5) Ensure composite PK (course_id, teacher_id)
        Schema::table('course_teacher', function (Blueprint $table) {
            try {
                $table->primary(['course_id', 'teacher_id'], 'course_teacher_pk');
            } catch (\Throwable $e) {
                // ignore
            }
        });

        // 6) Recreate FKs with fixed names (and avoid errno 121 duplicates)
        // drop any FK currently on these cols (by discovery) then add named constraints
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
            if (Schema::hasColumn('course_teacher', $col)) {
                $this->dropAllForeignKeysForColumn('course_teacher', $col);
            }
        }

        // drop PK
        $this->dropPrimaryIfExists('course_teacher');

        // drop index
        $this->dropIndexIfExists('course_teacher', 'course_teacher_teacher_idx');

        // rename back if needed
        if (!Schema::hasTable('course_trainer')) {
            Schema::rename('course_teacher', 'course_trainer');
        }

        // best-effort restore old column (user_id) if missing
        if (Schema::hasTable('course_trainer')) {
            $cols = Schema::getColumnListing('course_trainer');
            $has = fn (string $c) => in_array($c, $cols, true);

            if (!$has('user_id')) {
                Schema::table('course_trainer', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->after('course_id');
                });
            }

            Schema::table('course_trainer', function (Blueprint $table) {
                try { $table->primary(['course_id', 'user_id'], 'course_trainer_pk'); } catch (\Throwable $e) {}
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
