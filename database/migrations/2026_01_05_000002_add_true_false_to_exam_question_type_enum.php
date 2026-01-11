<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE exam_questions MODIFY COLUMN type ENUM('mcq', 'essay', 'true_false') NOT NULL");
        } else {
            Schema::table('exam_questions', function (Blueprint $table) {
                $table->enum('type', ['mcq', 'essay', 'true_false'])->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE exam_questions MODIFY COLUMN type ENUM('mcq', 'essay') NOT NULL");
        } else {
            Schema::table('exam_questions', function (Blueprint $table) {
                $table->enum('type', ['mcq', 'essay'])->change();
            });
        }
    }
};
