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
        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();

            $table->foreignId('lesson_id')
                ->nullable()
                ->constrained('lessons')
                ->nullOnDelete();

            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->nullOnDelete();

            $table->string('title');

            $table->enum('location_type', ['online', 'onsite'])
                ->default('online');

            $table->string('provider')->nullable();

            $table->string('room_slug')->nullable()->unique();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->enum('status', ['scheduled', 'completed', 'cancelled'])
                ->default('scheduled');

            $table->string('onsite_qr_secret')->nullable();

            $table->timestamps();

            $table->index(['course_id', 'status']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_sessions');
    }
};
