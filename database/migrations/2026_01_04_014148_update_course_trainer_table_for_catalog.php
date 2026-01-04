<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_trainer', function (Blueprint $table) {
            $table->dropPrimary(['course_id', 'user_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('course_trainer', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        Schema::table('course_trainer', function (Blueprint $table) {
            $table->foreignId('trainer_id')->after('course_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['course_id', 'trainer_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('course_trainer', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'trainer_id']);
            $table->dropTimestamps();
            $table->dropForeign(['trainer_id']);
            $table->dropColumn('trainer_id');
        });

        Schema::table('course_trainer', function (Blueprint $table) {
            $table->foreignId('user_id')->after('course_id')->constrained()->cascadeOnDelete();
            $table->primary(['course_id', 'user_id']);
        });
    }
};
