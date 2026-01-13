<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->unsignedBigInteger('teacher_id')->nullable()->after('parent_id');

            // لو جدول المدرسين اسمه teachers
            $table->foreign('teacher_id')
                ->references('id')
                ->on('teachers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropColumn('teacher_id');
        });
    }
};
