<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-vendor panel mode set by the admin:
     *   full       — the normal vendor panel (default, existing behaviour)
     *   stock_only — the vendor only sees the stock-management screens
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'panel_mode')) {
                $table->string('panel_mode', 20)->default('full')->after('product_auto_approve');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'panel_mode')) {
                $table->dropColumn('panel_mode');
            }
        });
    }
};
