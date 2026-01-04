<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')
                ->constrained('programs')
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            $table->string('code')->unique();

            $table->decimal('price', 12, 2);
            $table->boolean('is_installment_enabled')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['program_id', 'is_active']);
            $table->index(['branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        /**
         * لو في جداول تانية عاملة FK على courses
         * MySQL هيرفض DROP TABLE.
         *
         * في الـ rollback/dev نقدر نعطل FK checks مؤقتًا.
         */
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        Schema::dropIfExists('courses');

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
};
