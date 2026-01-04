<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');

            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);

            // self relation
            $table->unsignedBigInteger('parent_id')->nullable();

            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);

            // branch relation (make column first)
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->timestamps();

            // indexes (named)
            $table->index(['branch_id', 'is_active'], 'accounts_branch_active_idx');
            $table->index(['type', 'is_active'], 'accounts_type_active_idx');
        });

        // ✅ FK parent_id (accounts -> accounts)
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreign('parent_id', 'accounts_parent_id_foreign')
                ->references('id')
                ->on('accounts')
                ->nullOnDelete();
        });

        // ✅ FK branch_id only if branches table exists
        if (Schema::hasTable('branches')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->foreign('branch_id', 'accounts_branch_id_foreign')
                    ->references('id')
                    ->on('branches')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        // ✅ avoids 1451 when other tables reference accounts (e.g., journal_lines)
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        Schema::dropIfExists('accounts');

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
};
