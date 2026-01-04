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
        Schema::table('media_files', function (Blueprint $table) {
            $table->boolean('is_private')->default(true)->after('path');
            $table->string('access_token')->nullable()->unique()->after('is_private');
            $table->timestamp('expires_at')->nullable()->after('access_token');

            $table->index('is_private');
            $table->index('access_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropIndex(['is_private']);
            $table->dropIndex(['access_token']);
            $table->dropColumn(['is_private', 'access_token', 'expires_at']);
        });
    }
};
