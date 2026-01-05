<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('journal_lines', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('cost_center_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('journal_lines', 'enrollment_id')) {
                $table->foreignId('enrollment_id')->nullable()->after('user_id')->constrained('enrollments')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            if (Schema::hasColumn('journal_lines', 'enrollment_id')) {
                $table->dropForeign(['enrollment_id']);
                $table->dropColumn('enrollment_id');
            }
            if (Schema::hasColumn('journal_lines', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
