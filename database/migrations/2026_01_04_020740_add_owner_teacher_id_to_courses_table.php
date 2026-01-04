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
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('owner_teacher_id')->nullable()->after('branch_id')->constrained('teachers')->nullOnDelete();
            $table->index('owner_teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['owner_teacher_id']);
            $table->dropIndex(['owner_teacher_id']);
            $table->dropColumn('owner_teacher_id');
        });
    }
};
