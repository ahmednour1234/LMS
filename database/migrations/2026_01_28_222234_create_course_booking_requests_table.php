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
        Schema::create('course_booking_requests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 150);
            $table->string('educational_stage', 100);
            $table->string('phone', 20);
            $table->enum('gender', ['male', 'female']);
            $table->text('message');
            $table->string('status')->default('new');
            $table->text('admin_notes')->nullable();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->timestamps();

            $table->index('phone');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_booking_requests');
    }
};
