<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_sequences', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['receipt', 'payment'])->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamp('updated_at')->nullable();
        });

        DB::table('voucher_sequences')->insert([
            ['type' => 'receipt', 'last_number' => 0, 'updated_at' => now()],
            ['type' => 'payment', 'last_number' => 0, 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_sequences');
    }
};
