<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('couriers')) {
            return;
        }

        Schema::table('couriers', function (Blueprint $table) {
            // Whether vendors may select this courier when parcelling their own
            // orders. Default true preserves the existing "all active couriers
            // visible to vendors" behaviour.
            if (! Schema::hasColumn('couriers', 'vendor_allowed')) {
                $table->boolean('vendor_allowed')->default(true)->after('is_default');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('couriers') && Schema::hasColumn('couriers', 'vendor_allowed')) {
            Schema::table('couriers', function (Blueprint $table) {
                $table->dropColumn('vendor_allowed');
            });
        }
    }
};
