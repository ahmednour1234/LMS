<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('programs')->cascadeOnDelete();
            $table->json('name')->after('code');
            $table->json('description')->nullable()->after('name');
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['code', 'branch_id']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['code', 'branch_id']);
            $table->dropColumn(['parent_id', 'name', 'description']);
            $table->unique('code');
        });
    }
};
