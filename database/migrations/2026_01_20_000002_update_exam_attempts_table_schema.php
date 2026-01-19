<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('exam_attempts', 'status')) {
            DB::table('exam_attempts')->where('status', 'completed')->update(['status' => 'submitted']);
        }

        Schema::table('exam_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_attempts', 'attempt_no')) {
                $table->unsignedInteger('attempt_no')->default(1)->after('student_id');
            }
            if (!Schema::hasColumn('exam_attempts', 'graded_by_teacher_id')) {
                $table->foreignId('graded_by_teacher_id')->nullable()->after('graded_at')->constrained('teachers')->nullOnDelete();
            }
            if (Schema::hasColumn('exam_attempts', 'status')) {
                $table->enum('status', ['in_progress', 'submitted', 'graded'])->default('in_progress')->change();
            }
            if (Schema::hasColumn('exam_attempts', 'score')) {
                DB::statement('ALTER TABLE exam_attempts MODIFY COLUMN score INT DEFAULT 0');
            }
        });

        if (!Schema::hasIndex('exam_attempts', ['student_id', 'exam_id', 'attempt_no'])) {
            Schema::table('exam_attempts', function (Blueprint $table) {
                $table->unique(['student_id', 'exam_id', 'attempt_no'], 'exam_attempts_student_exam_attempt_unique');
            });
        }

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            if (Schema::hasIndex('exam_attempts', 'exam_attempts_student_exam_attempt_unique')) {
                $table->dropUnique('exam_attempts_student_exam_attempt_unique');
            }
            if (Schema::hasColumn('exam_attempts', 'attempt_no')) {
                $table->dropColumn('attempt_no');
            }
            if (Schema::hasColumn('exam_attempts', 'graded_by_teacher_id')) {
                $table->dropForeign(['graded_by_teacher_id']);
                $table->dropColumn('graded_by_teacher_id');
            }
            if (Schema::hasColumn('exam_attempts', 'score')) {
                DB::statement('ALTER TABLE exam_attempts MODIFY COLUMN score DECIMAL(8,2) DEFAULT 0');
            }
        });
    }
};