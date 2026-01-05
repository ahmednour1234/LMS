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
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('email')->index();
            $table->string('code', 6);
            $table->enum('purpose', ['register_verify', 'password_reset']);
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            
            $table->index(['email', 'purpose', 'expires_at']);
            $table->index(['email', 'code', 'purpose']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};

