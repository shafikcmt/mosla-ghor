<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Let a saved address fully drive checkout: store the delivery zone/location
     * (for charge) + BD hierarchy IDs. Additive + idempotent; no data loss.
     */
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_addresses', 'delivery_zone_id')) {
                $table->foreignId('delivery_zone_id')->nullable()->after('full_address')
                      ->constrained('delivery_zones')->nullOnDelete();
            }
            if (! Schema::hasColumn('customer_addresses', 'delivery_location_id')) {
                $table->foreignId('delivery_location_id')->nullable()->after('delivery_zone_id')
                      ->constrained('delivery_locations')->nullOnDelete();
            }
            if (! Schema::hasColumn('customer_addresses', 'delivery_area')) {
                $table->string('delivery_area', 30)->nullable()->after('delivery_location_id');
            }
            if (! Schema::hasColumn('customer_addresses', 'bd_division_id')) {
                $table->unsignedBigInteger('bd_division_id')->nullable()->after('delivery_area');
            }
            if (! Schema::hasColumn('customer_addresses', 'bd_district_id')) {
                $table->unsignedBigInteger('bd_district_id')->nullable()->after('bd_division_id');
            }
            if (! Schema::hasColumn('customer_addresses', 'bd_upazila_id')) {
                $table->unsignedBigInteger('bd_upazila_id')->nullable()->after('bd_district_id');
            }
            if (! Schema::hasColumn('customer_addresses', 'bd_union_id')) {
                $table->unsignedBigInteger('bd_union_id')->nullable()->after('bd_upazila_id');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep the new columns so saved addresses stay checkout-ready.
    }
};
