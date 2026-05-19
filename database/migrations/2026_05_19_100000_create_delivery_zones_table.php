<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('zone_name');
            $table->string('zone_type')->default('custom'); // inside_dhaka | outside_dhaka | custom
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->decimal('free_delivery_minimum_amount', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
