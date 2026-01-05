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
        Schema::table('students', function (Blueprint $table) {
            // Drop user_id foreign key and column
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            
            // Drop active field (we're using status enum instead)
            // Note: If this column doesn't exist, comment out this line
            $table->dropColumn('active');
            
            // Add email_verified_at
            $table->timestamp('email_verified_at')->nullable()->after('email');
            
            // Change status enum to only active/inactive and set default to inactive
            // First, we need to modify the enum
            $table->dropColumn('status');
        });
        
        // Add status column back with new enum values
        Schema::table('students', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('inactive')->after('password');
        });
        
        // Rename photo to image
        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('photo', 'image');
        });
        
        // Ensure email is unique
        Schema::table('students', function (Blueprint $table) {
            $table->unique('email');
        });
        
        // Ensure password is not nullable
        Schema::table('students', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Revert password to nullable
            $table->string('password')->nullable()->change();
            
            // Remove email unique constraint
            $table->dropUnique(['email']);
            
            // Rename image back to photo
            $table->renameColumn('image', 'photo');
            
            // Revert status enum
            $table->dropColumn('status');
        });
        
        Schema::table('students', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('password');
            // Add active field back
            $table->boolean('active')->default(true)->after('status');
        });
        
        Schema::table('students', function (Blueprint $table) {
            // Remove email_verified_at
            $table->dropColumn('email_verified_at');
            
            // Add user_id back
            $table->foreignId('user_id')->unique()->after('id')->constrained()->cascadeOnDelete();
        });
    }
};

