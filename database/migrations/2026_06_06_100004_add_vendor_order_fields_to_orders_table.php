<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            // customer_order | vendor_created_order | admin_created_order
            if (! Schema::hasColumn('orders', 'order_source'))         $table->string('order_source', 30)->default('customer_order')->after('order_type');
            if (! Schema::hasColumn('orders', 'created_by_vendor_id'))  $table->unsignedBigInteger('created_by_vendor_id')->nullable()->after('order_source');
            if (! Schema::hasColumn('orders', 'vendor_customer_id'))    $table->unsignedBigInteger('vendor_customer_id')->nullable()->after('created_by_vendor_id');

            if (! Schema::hasColumn('orders', 'discount_amount'))       $table->decimal('discount_amount', 12, 2)->default(0)->after('packaging_cost');
            if (! Schema::hasColumn('orders', 'partial_paid_amount'))   $table->decimal('partial_paid_amount', 12, 2)->default(0)->after('paid_amount');
            if (! Schema::hasColumn('orders', 'due_amount'))            $table->decimal('due_amount', 12, 2)->default(0)->after('partial_paid_amount');

            // Secure public tokens (never sequential ids)
            if (! Schema::hasColumn('orders', 'invoice_token'))         $table->string('invoice_token', 64)->nullable()->unique()->after('due_amount');
            if (! Schema::hasColumn('orders', 'payment_link_token'))    $table->string('payment_link_token', 64)->nullable()->unique()->after('invoice_token');
            if (! Schema::hasColumn('orders', 'reorder_token'))         $table->string('reorder_token', 64)->nullable()->unique()->after('payment_link_token');

            if (! Schema::hasColumn('orders', 'whatsapp_sent_at'))      $table->timestamp('whatsapp_sent_at')->nullable();
            if (! Schema::hasColumn('orders', 'customer_confirmed_at')) $table->timestamp('customer_confirmed_at')->nullable();
            if (! Schema::hasColumn('orders', 'invoice_disabled_at'))   $table->timestamp('invoice_disabled_at')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'order_source', 'created_by_vendor_id', 'vendor_customer_id',
                'discount_amount', 'partial_paid_amount', 'due_amount',
                'invoice_token', 'payment_link_token', 'reorder_token',
                'whatsapp_sent_at', 'customer_confirmed_at', 'invoice_disabled_at',
            ] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
