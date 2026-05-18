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
        Schema::create('price_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('markup_25g', 5, 2)->default(20);
            $table->decimal('markup_50g', 5, 2)->default(15);
            $table->decimal('markup_100g', 5, 2)->default(10);
            $table->decimal('markup_250g', 5, 2)->default(5);
            $table->decimal('markup_500g', 5, 2)->default(0);
            $table->decimal('markup_1000g', 5, 2)->default(0);
            $table->string('rounding_type')->default('nearest_5');
            $table->decimal('default_packaging_cost', 10, 2)->default(0);
            $table->string('currency_symbol')->default('৳');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_settings');
    }
};
