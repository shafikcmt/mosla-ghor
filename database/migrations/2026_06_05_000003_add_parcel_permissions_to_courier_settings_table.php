<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courier_settings')) {
            return;
        }

        Schema::table('courier_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('courier_settings', 'vendor_can_setup_pickup_address')) {
                $table->boolean('vendor_can_setup_pickup_address')->default(true)->after('vendor_can_mark_handover');
            }
            if (! Schema::hasColumn('courier_settings', 'vendor_can_create_parcel')) {
                $table->boolean('vendor_can_create_parcel')->default(false)->after('vendor_can_setup_pickup_address');
            }
            // New unified mode. Backfilled from the legacy courier_selection_mode below.
            // admin_only | vendor_can_request | vendor_can_parcel
            if (! Schema::hasColumn('courier_settings', 'vendor_courier_mode')) {
                $table->string('vendor_courier_mode', 30)->default('admin_only')->after('courier_selection_mode');
            }
        });

        // Backfill the new mode from the legacy selection mode (no data loss; the
        // old column is kept for back-compat but logic now reads the new column).
        if (Schema::hasColumn('courier_settings', 'vendor_courier_mode')
            && Schema::hasColumn('courier_settings', 'courier_selection_mode')) {
            $map = [
                'admin_only'     => 'admin_only',
                'vendor_suggest' => 'vendor_can_request',
                'vendor_select'  => 'vendor_can_parcel',
            ];
            foreach ($map as $old => $new) {
                DB::table('courier_settings')
                    ->where('courier_selection_mode', $old)
                    ->update(['vendor_courier_mode' => $new]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('courier_settings')) {
            return;
        }

        Schema::table('courier_settings', function (Blueprint $table) {
            foreach (['vendor_can_setup_pickup_address', 'vendor_can_create_parcel', 'vendor_courier_mode'] as $col) {
                if (Schema::hasColumn('courier_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
