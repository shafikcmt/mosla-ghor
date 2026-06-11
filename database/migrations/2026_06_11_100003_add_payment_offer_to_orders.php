<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persist the checkout payment offer on the order: prepaid discount, the
     * chosen mode, estimated delivery text, and the saved address used.
     * Additive + idempotent.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_discount')) {
                $table->decimal('payment_discount', 10, 2)->default(0)->after('delivery_charge');
            }
            if (! Schema::hasColumn('orders', 'payment_mode')) {
                $table->string('payment_mode', 20)->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('orders', 'estimated_delivery')) {
                $table->string('estimated_delivery', 60)->nullable()->after('payment_mode');
            }
            if (! Schema::hasColumn('orders', 'customer_address_id')) {
                $table->foreignId('customer_address_id')->nullable()->after('customer_id')
                      ->constrained('customer_addresses')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep the payment-offer columns.
    }
};
