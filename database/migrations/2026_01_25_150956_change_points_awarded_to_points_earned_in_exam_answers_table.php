<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('exam_answers', 'points_awarded') && !Schema::hasColumn('exam_answers', 'points_earned')) {
            DB::statement('ALTER TABLE exam_answers CHANGE points_awarded points_earned DECIMAL(8,2) DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('exam_answers', 'points_earned') && !Schema::hasColumn('exam_answers', 'points_awarded')) {
            DB::statement('ALTER TABLE exam_answers CHANGE points_earned points_awarded INT DEFAULT 0');
        }
    }
};
