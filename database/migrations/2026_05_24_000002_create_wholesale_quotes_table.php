<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesale_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')->constrained('wholesale_enquiries')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            // Pricing
            $table->decimal('unit_price', 10, 2);
            $table->decimal('quantity', 10, 2);
            $table->string('quantity_unit')->default('kg');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->decimal('advance_required', 10, 2)->default(0);
            $table->json('payment_options')->nullable(); // ['online','cod','partial']

            $table->text('note')->nullable();
            $table->date('valid_until')->nullable();

            // Status: pending=awaiting admin approval, approved=customer can see,
            //         accepted=customer accepted, rejected=rejected, expired=past valid_until
            $table->string('status', 50)->default('pending');

            // Admin approval gate
            $table->boolean('admin_approved')->default(false);
            $table->timestamp('admin_approved_at')->nullable();
            $table->foreignId('admin_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admin_rejected_at')->nullable();
            $table->text('admin_note')->nullable();

            // Once customer accepts and order is placed
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            $table->index(['enquiry_id', 'status']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_quotes');
    }
};
