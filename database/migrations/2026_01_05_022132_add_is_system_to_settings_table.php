<?php

use App\Models\Setting;
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
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('group');
        });

        // Mark all system settings as is_system = true
        $systemSettings = [
            'app_name',
            'app_email',
            'app_phone',
            'app_whatsapp',
            'default_branch',
            'currency',
            'fiscal_year_start',
            'fiscal_year_end',
            'tax_rate',
            'invoice_prefix',
            'receipt_prefix',
            'tax_registration_number',
            'commercial_registration_number',
        ];

        DB::table('settings')
            ->whereIn('key', $systemSettings)
            ->update(['is_system' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
