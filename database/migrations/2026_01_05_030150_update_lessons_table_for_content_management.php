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
        Schema::table('lessons', function (Blueprint $table) {
            // Drop old index before renaming
            $table->dropIndex(['section_id', 'order']);
        });

        // Rename order to sort_order
        if (config('database.default') === 'sqlite') {
            DB::statement('ALTER TABLE lessons RENAME COLUMN "order" TO "sort_order"');
        } else {
            Schema::table('lessons', function (Blueprint $table) {
                $table->renameColumn('order', 'sort_order');
            });
        }

        Schema::table('lessons', function (Blueprint $table) {
            // Add new fields (title and description already JSON, no change needed)
            $table->enum('lesson_type', ['recorded', 'live', 'mixed'])->default('recorded')->after('sort_order');
            $table->boolean('is_preview')->default(false)->after('lesson_type');
            $table->integer('estimated_minutes')->nullable()->after('is_preview');
            $table->dateTime('published_at')->nullable()->after('estimated_minutes');
            
            // Add indexes
            $table->index(['section_id', 'sort_order']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Drop new foreign key and indexes
            $table->dropForeign(['section_id']);
            $table->dropIndex(['section_id', 'sort_order']);
            $table->dropIndex(['published_at']);
            $table->dropIndex(['section_id']);

            // Drop new columns
            $table->dropColumn([
                'lesson_type',
                'is_preview',
                'estimated_minutes',
                'published_at',
            ]);
            $table->dropIndex(['section_id', 'sort_order']);
            $table->dropIndex(['published_at']);
        });

        // Rename sort_order back to order
        if (config('database.default') === 'sqlite') {
            DB::statement('ALTER TABLE lessons RENAME COLUMN "sort_order" TO "order"');
        } else {
            Schema::table('lessons', function (Blueprint $table) {
                $table->renameColumn('sort_order', 'order');
            });
        }

        Schema::table('lessons', function (Blueprint $table) {
            // Restore old index
            $table->index(['section_id', 'order']);
        });
    }
};
