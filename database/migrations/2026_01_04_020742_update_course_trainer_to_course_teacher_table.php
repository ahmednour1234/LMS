<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ---------- helpers ----------
    private function dropColumnSafe(string $table, string $column): void
    {
        try {
            DB::statement("ALTER TABLE `$table` DROP COLUMN `$column`");
        } catch (\Throwable $e) {
            // MySQL: 1072 = Key column doesn't exist
            // ignore any "doesn't exist" errors and continue
            // also ignore if table doesn't exist or already modified
        }
    }

    private function dropFkByDiscovery(string $table, string $column): void
    {
        try {
            $db = DB::getDatabaseName();

            $rows = DB::select("
                SELECT CONSTRAINT_NAME as name
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$db, $table, $column]);

            $names = array_values(array_unique(array_map(fn($r) => $r->name, $rows)));

            foreach ($names as $fk) {
                try {
                    DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$fk`");
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function dropPrimarySafe(string $table): void
    {
        try {
            DB::statement("ALTER TABLE `$table` DROP PRIMARY KEY");
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function dropIndexSafe(string $table, string $indexName): void
    {
        try {
            DB::statement("ALTER TABLE `$table` DROP INDEX `$indexName`");
        } catch (\Throwable $e) {
            // ignore (index doesn't exist)
        }
    }

    // ---------- migration ----------
    public function up(): void
    {
        $hasTrainer = Schema::hasTable('course_trainer');
        $hasTeacher = Schema::hasTable('course_teacher');

        // Rename old table if needed
        if ($hasTrainer && !$hasTeacher) {
            Schema::rename('course_trainer', 'course_teacher');
            $hasTeacher = true;
        }

        // Create if missing
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

        /**
         * Normalize existing course_teacher:
         * - remove trainer_id/user_id + timestamps
         * - ensure teacher_id
         * - ensure PK(course_id, teacher_id)
         * - ensure FK -> courses/teachers
         */

        // 1) Drop any FKs on possible legacy columns (best-effort)
        foreach (['course_id', 'teacher_id', 'trainer_id', 'user_id'] as $col) {
            $this->dropFkByDiscovery('course_teacher', $col);
        }

        // 2) Drop PK + common indexes (best-effort)
        $this->dropPrimarySafe('course_teacher');

        foreach ([
            'course_teacher_course_id_trainer_id_unique',
            'course_teacher_course_id_user_id_unique',
            'course_trainer_course_id_user_id_unique',
            'course_teacher_teacher_id_index',
            'course_teacher_user_id_index',
            'course_teacher_teacher_idx',
            'course_teacher_pk',
        ] as $idx) {
            $this->dropIndexSafe('course_teacher', $idx);
        }

        // 3) Drop legacy columns safely (NO checks, just try)
        $this->dropColumnSafe('course_teacher', 'trainer_id');
        $this->dropColumnSafe('course_teacher', 'user_id');
        $this->dropColumnSafe('course_teacher', 'created_at');
        $this->dropColumnSafe('course_teacher', 'updated_at');

        // 4) Ensure teacher_id column exists (this one لازم نعمله عبر Schema)
        if (!Schema::hasColumn('course_teacher', 'teacher_id')) {
            Schema::table('course_teacher', function (Blueprint $table) {
                $table->unsignedBigInteger('teacher_id')->after('course_id');
            });
        }

        // 5) Ensure teacher_id index
        try {
            Schema::table('course_teacher', function (Blueprint $table) {
                $table->index('teacher_id', 'course_teacher_teacher_idx');
            });
        } catch (\Throwable $e) {
            // ignore (already exists)
        }

        // 6) Ensure composite PK
        try {
            Schema::table('course_teacher', function (Blueprint $table) {
                $table->primary(['course_id', 'teacher_id'], 'course_teacher_pk');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        // 7) Add FKs with fixed names (best-effort)
        // Drop any FKs currently on course_id/teacher_id then add ours
        $this->dropFkByDiscovery('course_teacher', 'course_id');
        $this->dropFkByDiscovery('course_teacher', 'teacher_id');

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

        // Drop FKs best-effort
        foreach (['course_id', 'teacher_id'] as $col) {
            $this->dropFkByDiscovery('course_teacher', $col);
        }

        // Drop PK + index
        $this->dropPrimarySafe('course_teacher');
        $this->dropIndexSafe('course_teacher', 'course_teacher_teacher_idx');

        // Rename back
        if (!Schema::hasTable('course_trainer')) {
            Schema::rename('course_teacher', 'course_trainer');
        }

        // Restore user_id best-effort
        if (Schema::hasTable('course_trainer') && !Schema::hasColumn('course_trainer', 'user_id')) {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->after('course_id');
            });
        }

        // Restore PK/FKs best-effort
        try {
            Schema::table('course_trainer', function (Blueprint $table) {
                $table->primary(['course_id', 'user_id'], 'course_trainer_pk');
            });
        } catch (\Throwable $e) {}

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
};
