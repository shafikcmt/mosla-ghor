<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-product unit conversions, e.g. [{"unit":"carton","qty":20,"base":"kg"}] meaning
     * "1 carton = 20 kg". Used to price পাইকারি units from wholesale_price_1kg (which
     * already exists). Additive + idempotent.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'unit_conversions')) {
                $table->json('unit_conversions')->nullable()->after('min_order_unit');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep the column so no conversion config is lost on rollback.
    }
};
