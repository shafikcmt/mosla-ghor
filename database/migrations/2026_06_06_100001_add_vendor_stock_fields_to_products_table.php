<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'sku'))                 $table->string('sku', 100)->nullable()->after('slug');
            if (! Schema::hasColumn('products', 'category'))            $table->string('category', 100)->nullable()->after('sku');
            if (! Schema::hasColumn('products', 'brand'))               $table->string('brand', 100)->nullable()->after('category');
            if (! Schema::hasColumn('products', 'unit'))                $table->string('unit', 20)->nullable()->default('kg')->after('brand');
            if (! Schema::hasColumn('products', 'purchase_price'))      $table->decimal('purchase_price', 12, 2)->nullable()->after('wholesale_price_1kg');
            if (! Schema::hasColumn('products', 'selling_price'))       $table->decimal('selling_price', 12, 2)->nullable()->after('purchase_price');
            // stock_qty: authoritative on-hand for unit-managed (non-kg-pack) products.
            // Legacy spice products keep using the existing whole-kg `stock` column.
            if (! Schema::hasColumn('products', 'stock_qty'))           $table->decimal('stock_qty', 12, 3)->nullable()->after('stock');
            if (! Schema::hasColumn('products', 'low_stock_threshold')) $table->decimal('low_stock_threshold', 12, 3)->default(0)->after('stock_qty');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            foreach (['sku', 'category', 'brand', 'unit', 'purchase_price', 'selling_price', 'stock_qty', 'low_stock_threshold'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
