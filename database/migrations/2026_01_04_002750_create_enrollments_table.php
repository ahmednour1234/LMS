<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            // مهم في بعض السيرفرات للتأكد من دعم FK
            $table->engine = 'InnoDB';

            $table->id();

            $table->string('reference', 64)->unique();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();

            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])
                ->default('pending');

            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('registered_at')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['student_id', 'course_id']);
            $table->index('status');
            $table->index('branch_id');
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
