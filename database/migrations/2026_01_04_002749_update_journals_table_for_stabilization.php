<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->foreignId('posted_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
        });

        // Rename date to journal_date
        Schema::table('journals', function (Blueprint $table) {
            $table->renameColumn('date', 'journal_date');
        });

        // Update status enum to include 'void'
        // Note: MySQL/MariaDB requires recreating the column
        DB::statement("ALTER TABLE journals MODIFY COLUMN status ENUM('draft', 'posted', 'void') DEFAULT 'draft'");

        // Add unique index on (reference_type, reference_id) where both are not null
        // This requires a partial unique index - MySQL doesn't support this directly
        // We'll use a unique index with a check constraint or a unique index on the combination
        Schema::table('journals', function (Blueprint $table) {
            $table->unique(['reference_type', 'reference_id'], 'journals_reference_unique');
        });

        // Update indexes for journal_date
        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex(['date', 'status']);
            $table->index(['journal_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex('journals_reference_unique');
            $table->dropIndex(['journal_date', 'status']);
            $table->index(['date', 'status']);
        });

        DB::statement("ALTER TABLE journals MODIFY COLUMN status ENUM('draft', 'posted') DEFAULT 'draft'");

        Schema::table('journals', function (Blueprint $table) {
            $table->renameColumn('journal_date', 'date');
            $table->dropForeign(['posted_by']);
            $table->dropColumn('posted_by');
        });
    }
};
