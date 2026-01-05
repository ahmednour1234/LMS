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
        Schema::table('lessons', function (Blueprint $table) {
            // Drop old foreign key and index
            $table->dropForeign(['section_id']);
            $table->dropIndex(['section_id', 'order']);

            // Change column types and rename order to sort_order
            $table->dropColumn(['title', 'description', 'order']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->string('title')->after('section_id');
            $table->text('description')->nullable()->after('title');
            $table->integer('sort_order')->default(0)->after('description');
            
            // Add new fields
            $table->enum('lesson_type', ['recorded', 'live', 'mixed'])->default('recorded')->after('sort_order');
            $table->boolean('is_preview')->default(false)->after('lesson_type');
            $table->integer('estimated_minutes')->nullable()->after('is_preview');
            $table->dateTime('published_at')->nullable()->after('estimated_minutes');

            // Add new foreign key to sections table
            $table->foreign('section_id')->references('id')->on('sections')->cascadeOnDelete();
            
            // Add indexes
            $table->index('section_id');
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
                'title',
                'description',
                'sort_order',
            ]);
        });

        Schema::table('lessons', function (Blueprint $table) {
            // Restore old structure
            $table->json('title')->after('section_id');
            $table->json('description')->nullable()->after('title');
            $table->unsignedInteger('order')->default(0)->after('description');

            // Restore old foreign key
            $table->foreign('section_id')->references('id')->on('course_sections')->cascadeOnDelete();
            $table->index(['section_id', 'order']);
        });
    }
};
