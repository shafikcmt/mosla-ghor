<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesale_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();

            // Enquiry details (visible to vendor)
            $table->decimal('quantity_kg', 10, 2)->default(1);
            $table->string('delivery_location');
            $table->string('business_type'); // shop/restaurant/dealer/retailer/other
            $table->text('message')->nullable();

            // Customer contact — HIDDEN FROM VENDOR, Admin only
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_whatsapp')->nullable();

            // Snapshot of product name at enquiry time
            $table->string('product_name');

            $table->string('status')->default('pending');
            // pending → quoted → accepted → completed | rejected | cancelled

            $table->text('vendor_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_enquiries');
    }
};
