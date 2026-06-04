<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paykari_combo_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();

            // Customer contact — HIDDEN FROM VENDOR, Admin only
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_whatsapp')->nullable();

            $table->string('delivery_location');
            $table->string('business_type'); // shop/restaurant/dealer/retailer/other
            $table->text('message')->nullable();

            $table->string('status', 50)->default('pending');
            // pending → quoted → accepted → completed | rejected | cancelled

            $table->text('admin_note')->nullable();
            $table->text('vendor_note')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paykari_combo_enquiries');
    }
};
