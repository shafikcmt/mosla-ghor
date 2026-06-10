<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-product wholesale (Paykari) controls. Additive + idempotent — existing
     * products default to retail (is_wholesale = false), so retail behavior is unchanged.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'is_wholesale')) {
                $table->boolean('is_wholesale')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('products', 'wholesale_enquiry_enabled')) {
                $table->boolean('wholesale_enquiry_enabled')->default(true)->after('is_wholesale');
            }
            if (! Schema::hasColumn('products', 'min_order_quantity')) {
                $table->decimal('min_order_quantity', 10, 2)->nullable()->after('wholesale_enquiry_enabled');
            }
            if (! Schema::hasColumn('products', 'min_order_unit')) {
                $table->string('min_order_unit', 20)->default('kg')->after('min_order_quantity');
            }
            if (! Schema::hasColumn('products', 'delivery_time')) {
                $table->string('delivery_time')->nullable()->after('min_order_unit');
            }
            if (! Schema::hasColumn('products', 'payment_terms')) {
                $table->string('payment_terms')->nullable()->after('delivery_time');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep columns so no wholesale config is lost on rollback.
    }
};
