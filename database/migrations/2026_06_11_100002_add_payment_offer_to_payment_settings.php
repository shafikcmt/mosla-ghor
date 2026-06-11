<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Meesho-style payment offer: COD vs Instant (prepaid) with a discount +
     * per-mode delivery estimate. Additive + idempotent.
     */
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_settings', 'instant_payment_enabled')) {
                $table->boolean('instant_payment_enabled')->default(true)->after('cash_on_delivery_enabled');
            }
            if (! Schema::hasColumn('payment_settings', 'instant_discount_type')) {
                $table->string('instant_discount_type', 20)->default('percentage')->after('instant_payment_enabled');
            }
            if (! Schema::hasColumn('payment_settings', 'instant_discount_value')) {
                $table->decimal('instant_discount_value', 10, 2)->default(10)->after('instant_discount_type');
            }
            if (! Schema::hasColumn('payment_settings', 'instant_min_order_amount')) {
                $table->decimal('instant_min_order_amount', 10, 2)->nullable()->after('instant_discount_value');
            }
            if (! Schema::hasColumn('payment_settings', 'cod_delivery_days')) {
                $table->string('cod_delivery_days', 30)->default('৫–৭')->after('instant_min_order_amount');
            }
            if (! Schema::hasColumn('payment_settings', 'instant_delivery_days')) {
                $table->string('instant_delivery_days', 30)->default('২–৩')->after('cod_delivery_days');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep the payment-offer columns.
    }
};
