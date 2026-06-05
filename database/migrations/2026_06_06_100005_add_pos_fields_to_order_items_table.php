<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_items')) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            // Free-form POS line fields (legacy gram-based lines keep quantity_gram).
            if (! Schema::hasColumn('order_items', 'quantity'))        $table->decimal('quantity', 12, 3)->nullable()->after('quantity_gram');
            if (! Schema::hasColumn('order_items', 'unit'))            $table->string('unit', 20)->nullable()->after('quantity');
            if (! Schema::hasColumn('order_items', 'discount_amount')) $table->decimal('discount_amount', 12, 2)->default(0)->after('unit_price');
        });

        // quantity_gram is NOT NULL by default; free-form POS lines have no grams.
        if (Schema::hasColumn('order_items', 'quantity_gram')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->unsignedInteger('quantity_gram')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_items')) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            foreach (['quantity', 'unit', 'discount_amount'] as $col) {
                if (Schema::hasColumn('order_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
