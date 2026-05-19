<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained('couriers')->cascadeOnDelete();
            $table->foreignId('delivery_zone_id')->nullable()->constrained('delivery_zones')->nullOnDelete();
            $table->string('zone_type')->nullable();
            $table->integer('min_weight')->default(0);
            $table->integer('max_weight')->default(1000);
            $table->decimal('courier_cost', 10, 2)->default(0);
            $table->decimal('customer_delivery_charge', 10, 2)->default(0);
            $table->decimal('cod_percentage', 5, 2)->default(0);
            $table->decimal('return_charge', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_rates');
    }
};
