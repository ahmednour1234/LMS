<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop all foreign keys first (both course_id and user_id have foreign keys)
        Schema::table('course_trainer', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['user_id']);
        });

        // Then drop primary key
        Schema::table('course_trainer', function (Blueprint $table) {
            $table->dropPrimary(['course_id', 'user_id']);
        });

        // Drop the old column
        Schema::table('course_trainer', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        // Add new column, re-add course_id foreign key, and add constraints
        Schema::table('course_trainer', function (Blueprint $table) {
            $table->foreignId('trainer_id')->after('course_id')->constrained('users')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
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
            $table->dropForeign(['course_id']);
            $table->dropColumn('trainer_id');
        });

        Schema::table('course_trainer', function (Blueprint $table) {
            $table->foreignId('user_id')->after('course_id')->constrained()->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->primary(['course_id', 'user_id']);
        });
    }
};
