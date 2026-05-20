<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id')->nullable()->after('product_id');
            $table->string('unit_name', 50)->nullable()->after('label');
            $table->string('unit_value', 50)->nullable()->after('unit_name');
            $table->decimal('compare_price', 10, 2)->nullable()->after('final_price');
            $table->integer('sort_order')->default(0)->after('is_active');

            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn(['product_variant_id', 'unit_name', 'unit_value', 'compare_price', 'sort_order']);
        });
    }
};
