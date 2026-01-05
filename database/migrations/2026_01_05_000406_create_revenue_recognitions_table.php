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
        Schema::create('revenue_recognitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->decimal('recognized_amount', 15, 2);
            $table->timestamp('recognized_at');
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->timestamps();

            $table->index('enrollment_id');
            $table->index('journal_id');
            $table->index('recognized_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_recognitions');
    }
};
