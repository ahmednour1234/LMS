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

            $table->id(); // BIGINT UNSIGNED

            $table->string('reference', 64)->unique();

            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])
                ->default('pending');

            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('registered_at')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
