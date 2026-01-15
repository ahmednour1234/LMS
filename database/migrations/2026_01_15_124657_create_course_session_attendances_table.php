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
        Schema::create('course_session_attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')
                ->constrained('course_sessions')
                ->cascadeOnDelete();

            $table->foreignId('enrollment_id')
                ->constrained('enrollments')
                ->cascadeOnDelete();

            $table->enum('status', ['present', 'absent', 'late', 'excused'])
                ->default('absent');

            $table->enum('method', ['manual', 'qr'])
                ->default('manual');

            $table->foreignId('marked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('marked_at')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['session_id', 'enrollment_id']);
            $table->index(['session_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_session_attendances');
    }
};
