<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add WooCommerce-style per-variant fields. All nullable / defaulted so
     * existing variants and products stay valid. Legacy origin/grade/size_label
     * columns are left in place (unused by the UI) — no destructive drops.
     */
    public function up(): void
    {
        if (! Schema::hasTable('product_variants')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variants', 'sku')) {
                $table->string('sku')->nullable()->after('name');
            }
            if (! Schema::hasColumn('product_variants', 'retail_price')) {
                $table->decimal('retail_price', 10, 2)->nullable()->after('image');
            }
            if (! Schema::hasColumn('product_variants', 'sale_price')) {
                $table->decimal('sale_price', 10, 2)->nullable()->after('retail_price');
            }
            if (! Schema::hasColumn('product_variants', 'stock')) {
                $table->integer('stock')->nullable()->after('sale_price');
            }
            if (! Schema::hasColumn('product_variants', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_variants')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            foreach (['sku', 'retail_price', 'sale_price', 'stock', 'is_default'] as $col) {
                if (Schema::hasColumn('product_variants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
