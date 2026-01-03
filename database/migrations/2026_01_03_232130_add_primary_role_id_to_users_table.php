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
        $tableNames = config('permission.table_names');
        
        Schema::table('users', function (Blueprint $table) use ($tableNames) {
            $table->foreignId('primary_role_id')
                ->nullable()
                ->after('branch_id')
                ->constrained($tableNames['roles'])
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['primary_role_id']);
            $table->dropColumn('primary_role_id');
        });
    }
};
