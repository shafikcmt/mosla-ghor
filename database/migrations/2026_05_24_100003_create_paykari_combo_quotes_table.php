<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paykari_combo_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_enquiry_id')
                  ->constrained('paykari_combo_enquiries')
                  ->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();

            // Item-wise quote stored as JSON
            // [{product_id, product_name, unit_price, quantity_kg, subtotal}]
            $table->json('items');

            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->boolean('advance_required')->default(false);
            $table->decimal('advance_amount', 10, 2)->nullable();
            $table->json('payment_options')->nullable(); // ['cod','bkash','nagad','bank']
            $table->text('note')->nullable();
            $table->date('valid_until')->nullable();

            $table->string('status')->default('pending'); // pending/approved/rejected
            $table->boolean('admin_approved')->default(false);
            $table->text('admin_note')->nullable();
            $table->string('customer_response')->nullable(); // accepted/declined

            $table->timestamps();

            $table->index(['combo_enquiry_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paykari_combo_quotes');
    }
};
