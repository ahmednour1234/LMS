<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->bigIncrements('id'); // ممكن تخليه increments لو تحب، بس خليها ثابتة عندك

            $table->string('reference', 64)->unique();

            // FK types matching students/courses INT
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('course_id');

            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');

            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->text('notes')->nullable();

            // لو branches/users INT برضه:
            $table->unsignedInteger('branch_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();

            $table->timestamps();

            // Constraints
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();

            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

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
