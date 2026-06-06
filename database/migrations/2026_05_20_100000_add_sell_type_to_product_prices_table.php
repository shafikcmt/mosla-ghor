<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_prices')) {
            return;
        }

        Schema::table('product_prices', function (Blueprint $table) {
            if (! Schema::hasColumn('product_prices', 'sell_type')) {
                $table->string('sell_type', 20)->default('retail')->after('product_id');
            }
            if (! Schema::hasColumn('product_prices', 'min_order_qty')) {
                $table->unsignedInteger('min_order_qty')->nullable()->after('is_active');
            }
        });

        // Create the new composite unique FIRST. It has product_id as its
        // leftmost column, so the product_id foreign key can lean on it. On
        // MySQL an index still required by an FK cannot be dropped, so the old
        // unique must only be dropped once a replacement index exists.
        Schema::table('product_prices', function (Blueprint $table) {
            if (! Schema::hasIndex('product_prices', 'product_prices_product_id_quantity_gram_sell_type_unique')) {
                $table->unique(['product_id', 'quantity_gram', 'sell_type']);
            }
        });

        Schema::table('product_prices', function (Blueprint $table) {
            if (Schema::hasIndex('product_prices', 'product_prices_product_id_quantity_gram_unique')) {
                $table->dropUnique('product_prices_product_id_quantity_gram_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_prices')) {
            return;
        }

        Schema::table('product_prices', function (Blueprint $table) {
            if (! Schema::hasIndex('product_prices', 'product_prices_product_id_quantity_gram_unique')) {
                $table->unique(['product_id', 'quantity_gram']);
            }
        });

        Schema::table('product_prices', function (Blueprint $table) {
            if (Schema::hasIndex('product_prices', 'product_prices_product_id_quantity_gram_sell_type_unique')) {
                $table->dropUnique('product_prices_product_id_quantity_gram_sell_type_unique');
            }
        });

        Schema::table('product_prices', function (Blueprint $table) {
            foreach (['sell_type', 'min_order_qty'] as $column) {
                if (Schema::hasColumn('product_prices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
