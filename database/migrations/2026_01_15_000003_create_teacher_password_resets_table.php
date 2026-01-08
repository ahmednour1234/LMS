<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('code', 6)->index();
            $table->timestamp('expires_at');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_password_resets');
    }
};

