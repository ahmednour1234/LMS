<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_answers', 'answer_text')) {
                $table->text('answer_text')->nullable()->after('question_id');
            }
            if (!Schema::hasColumn('exam_answers', 'selected_option')) {
                $table->string('selected_option')->nullable()->after('answer_text');
            }
        });

        if (Schema::hasColumn('exam_answers', 'points_earned') && !Schema::hasColumn('exam_answers', 'points_awarded')) {
            DB::statement('ALTER TABLE exam_answers CHANGE points_earned points_awarded INT DEFAULT 0');
        } elseif (!Schema::hasColumn('exam_answers', 'points_awarded')) {
            Schema::table('exam_answers', function (Blueprint $table) {
                $table->integer('points_awarded')->default(0)->after('is_correct');
            });
        }

        if (Schema::hasColumn('exam_answers', 'points_awarded')) {
            $columnType = Schema::getColumnType('exam_answers', 'points_awarded');
            if ($columnType !== 'integer' && $columnType !== 'int') {
                DB::statement('ALTER TABLE exam_answers MODIFY COLUMN points_awarded INT DEFAULT 0');
            }
        }

        Schema::table('exam_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_answers', 'feedback')) {
                $table->text('feedback')->nullable()->after('points_awarded');
            }
        });

        if (Schema::hasColumn('exam_answers', 'answer') && !Schema::hasColumn('exam_answers', 'answer_text')) {
            DB::statement('ALTER TABLE exam_answers MODIFY COLUMN answer TEXT NULL');
        }

        if (!Schema::hasIndex('exam_answers', ['attempt_id', 'question_id'])) {
            Schema::table('exam_answers', function (Blueprint $table) {
                $table->unique(['attempt_id', 'question_id'], 'exam_answers_attempt_question_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            if (Schema::hasIndex('exam_answers', 'exam_answers_attempt_question_unique')) {
                $table->dropUnique('exam_answers_attempt_question_unique');
            }
            if (Schema::hasColumn('exam_answers', 'answer_text')) {
                $table->dropColumn('answer_text');
            }
            if (Schema::hasColumn('exam_answers', 'selected_option')) {
                $table->dropColumn('selected_option');
            }
            if (Schema::hasColumn('exam_answers', 'feedback')) {
                $table->dropColumn('feedback');
            }
        });

        if (Schema::hasColumn('exam_answers', 'points_awarded') && !Schema::hasColumn('exam_answers', 'points_earned')) {
            DB::statement('ALTER TABLE exam_answers CHANGE points_awarded points_earned DECIMAL(8,2) DEFAULT 0');
        }
    }
};