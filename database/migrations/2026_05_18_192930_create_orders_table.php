<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->string('customer_name', 100);
            $table->string('mobile_number', 20);
            $table->string('alternative_number', 20)->nullable();
            $table->text('full_address');
            $table->string('district', 80);
            $table->string('area', 80);
            $table->text('order_note')->nullable();
            $table->string('order_type', 30)->default('custom');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('packaging_cost', 10, 2)->default(0);
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->string('payment_method', 30)->default('cash_on_delivery');
            $table->string('payment_status', 30)->default('pending');
            $table->string('order_status', 30)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
