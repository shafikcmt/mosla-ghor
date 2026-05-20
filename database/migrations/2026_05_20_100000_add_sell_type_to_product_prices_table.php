<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->string('sell_type', 20)->default('retail')->after('product_id');
            $table->unsignedInteger('min_order_qty')->nullable()->after('is_active');

            // Drop old unique; new one includes sell_type so same gram can exist at retail + wholesale
            $table->dropUnique('product_prices_product_id_quantity_gram_unique');
            $table->unique(['product_id', 'quantity_gram', 'sell_type']);
        });
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'quantity_gram', 'sell_type']);
            $table->unique(['product_id', 'quantity_gram']);
            $table->dropColumn(['sell_type', 'min_order_qty']);
        });
    }
};
