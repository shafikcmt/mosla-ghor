<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_zone_id')->nullable()->after('delivery_area');
            $table->unsignedBigInteger('delivery_location_id')->nullable()->after('delivery_zone_id');
            $table->string('delivery_zone_name')->nullable()->after('delivery_location_id');
            $table->string('delivery_location_name')->nullable()->after('delivery_zone_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_zone_id', 'delivery_location_id', 'delivery_zone_name', 'delivery_location_name']);
        });
    }
};
