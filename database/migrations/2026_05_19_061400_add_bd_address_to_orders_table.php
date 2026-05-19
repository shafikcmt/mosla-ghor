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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('bd_division_id')->nullable()->after('delivery_location_name');
            $table->unsignedBigInteger('bd_district_id')->nullable()->after('bd_division_id');
            $table->unsignedBigInteger('bd_upazila_id')->nullable()->after('bd_district_id');
            $table->unsignedBigInteger('bd_union_id')->nullable()->after('bd_upazila_id');
            $table->string('division_name')->nullable()->after('bd_union_id');
            $table->string('district_name')->nullable()->after('division_name');
            $table->string('upazila_name')->nullable()->after('district_name');
            $table->string('union_name')->nullable()->after('upazila_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'bd_division_id', 'bd_district_id', 'bd_upazila_id', 'bd_union_id',
                'division_name', 'district_name', 'upazila_name', 'union_name',
            ]);
        });
    }
};
