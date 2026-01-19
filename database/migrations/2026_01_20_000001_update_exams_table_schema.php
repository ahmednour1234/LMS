<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'total_score')) {
                DB::statement('ALTER TABLE exams MODIFY COLUMN total_score INT DEFAULT 0');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'total_score')) {
                DB::statement('ALTER TABLE exams MODIFY COLUMN total_score DECIMAL(8,2) DEFAULT 0');
            }
        });
    }
};