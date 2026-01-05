<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function dropColumnSafe(string $table, string $column): void
    {
        try {
            DB::statement("ALTER TABLE `$table` DROP COLUMN `$column`");
        } catch (\Throwable $e) {
            // ignore (column doesn't exist / already dropped)
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
            // ignore
        }
    }

    public function up(): void
    {
        // لو الجدول مش موجود مفيش حاجة
        if (!Schema::hasTable('course_teacher')) {
            return;
        }

        // 1) خلي trainer_id اختياري أو شيله (الأفضل شيله)
        // لو عندك بيانات قديمة ومحتاجها: بدل drop -> اجعله nullable
        // هنا: هنشيله نهائيًا عشان pivot تبقى صحيحة
        $this->dropColumnSafe('course_teacher', 'trainer_id');
        $this->dropColumnSafe('course_teacher', 'user_id');
        $this->dropColumnSafe('course_teacher', 'created_at');
        $this->dropColumnSafe('course_teacher', 'updated_at');

        // 2) تأكد إن PK/unique صح
        $this->dropPrimarySafe('course_teacher');

        // drop common old indexes (best effort)
        foreach ([
            'course_teacher_course_id_trainer_id_unique',
            'course_teacher_course_id_user_id_unique',
            'course_trainer_course_id_user_id_unique',
            'course_teacher_teacher_id_index',
            'course_teacher_user_id_index',
            'course_teacher_teacher_idx',
        ] as $idx) {
            $this->dropIndexSafe('course_teacher', $idx);
        }

        // 3) أنشئ PK مركّب + index
        try {
            DB::statement("ALTER TABLE `course_teacher` ADD PRIMARY KEY (`course_id`, `teacher_id`)");
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::statement("ALTER TABLE `course_teacher` ADD INDEX `course_teacher_teacher_idx` (`teacher_id`)");
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // rollback best-effort
        if (!Schema::hasTable('course_teacher')) {
            return;
        }

        $this->dropPrimarySafe('course_teacher');
        $this->dropIndexSafe('course_teacher', 'course_teacher_teacher_idx');
    }
};
