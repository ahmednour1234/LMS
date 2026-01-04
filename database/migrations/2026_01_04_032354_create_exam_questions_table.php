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
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->enum('type', ['mcq', 'essay']);
            $table->json('question');
            $table->json('options')->nullable();
            $table->string('correct_answer')->nullable();
            $table->decimal('points', 8, 2)->default(0);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['exam_id', 'order']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
