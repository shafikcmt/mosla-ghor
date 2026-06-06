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

        // The composite unique is the only index supporting the product_id
        // foreign key. Add a plain index on product_id first so MySQL still has
        // an index for the FK, then drop the unique. (Error 1553 otherwise.)
        Schema::table('product_prices', function (Blueprint $table) {
            if (! Schema::hasIndex('product_prices', 'product_prices_product_id_index')) {
                $table->index('product_id');
            }
        });

        Schema::table('product_prices', function (Blueprint $table) {
            if (Schema::hasIndex('product_prices', 'product_prices_product_id_quantity_gram_sell_type_unique')) {
                $table->dropUnique('product_prices_product_id_quantity_gram_sell_type_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_prices')) {
            return;
        }

        Schema::table('product_prices', function (Blueprint $table) {
            if (! Schema::hasIndex('product_prices', 'product_prices_product_id_quantity_gram_sell_type_unique')) {
                $table->unique(['product_id', 'quantity_gram', 'sell_type']);
            }
        });

        Schema::table('product_prices', function (Blueprint $table) {
            if (Schema::hasIndex('product_prices', 'product_prices_product_id_index')) {
                $table->dropIndex('product_prices_product_id_index');
            }
        });
    }
};
