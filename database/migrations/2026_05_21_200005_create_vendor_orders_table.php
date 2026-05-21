<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->string('commission_type')->nullable();
            $table->decimal('commission_value_snapshot', 8, 2)->nullable();
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->decimal('payable_amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['order_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_orders');
    }
};
