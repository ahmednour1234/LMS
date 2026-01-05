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
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'pricing_type')) {
                $table->enum('pricing_type', ['full', 'installment'])->default('full')->after('status');
            }
            if (!Schema::hasColumn('enrollments', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0)->after('pricing_type');
            }
            if (!Schema::hasColumn('enrollments', 'progress_percent')) {
                $table->decimal('progress_percent', 5, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('enrollments', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('registered_at');
            }
            if (!Schema::hasColumn('enrollments', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }
            if (!Schema::hasColumn('enrollments', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('student_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('enrollments', 'completed_at')) {
                $table->dropColumn('completed_at');
            }
            if (Schema::hasColumn('enrollments', 'started_at')) {
                $table->dropColumn('started_at');
            }
            if (Schema::hasColumn('enrollments', 'progress_percent')) {
                $table->dropColumn('progress_percent');
            }
            if (Schema::hasColumn('enrollments', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
            if (Schema::hasColumn('enrollments', 'pricing_type')) {
                $table->dropColumn('pricing_type');
            }
        });
    }
};
