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
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->text('answer')->nullable();
            $table->decimal('points_earned', 8, 2)->default(0);
            $table->decimal('points_possible', 8, 2)->default(0);
            $table->text('feedback')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamps();

            $table->index(['attempt_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
    }
};
