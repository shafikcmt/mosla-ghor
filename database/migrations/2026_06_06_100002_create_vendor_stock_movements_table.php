<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_stock_movements')) {
            return;
        }

        Schema::create('vendor_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->nullable();   // null = admin-owned product
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id')->nullable();

            // add | reduce | adjustment | order | return | cancel
            $table->string('type', 20);

            $table->decimal('quantity', 12, 3)->default(0);        // signed change applied
            $table->decimal('previous_stock', 12, 3)->default(0);
            $table->decimal('new_stock', 12, 3)->default(0);

            $table->string('reference_type', 50)->nullable();      // e.g. order
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();   // user id (vendor/admin)

            $table->timestamps();

            $table->index(['vendor_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_stock_movements');
    }
};
