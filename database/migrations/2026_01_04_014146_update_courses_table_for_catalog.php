<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->json('name')->after('code');
            $table->json('description')->nullable()->after('name');
            $table->enum('delivery_type', ['onsite', 'online', 'virtual'])->after('description');
            $table->integer('duration_hours')->nullable()->after('delivery_type');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['price', 'is_installment_enabled']);
            $table->dropUnique(['code']);
            $table->unique(['code', 'branch_id']);
            $table->index('delivery_type');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['code', 'branch_id']);
            $table->dropIndex(['delivery_type']);
            $table->dropColumn(['name', 'description', 'delivery_type', 'duration_hours']);
            $table->decimal('price', 12, 2)->after('code');
            $table->boolean('is_installment_enabled')->default(false)->after('price');
            $table->unique('code');
        });
    }
};
