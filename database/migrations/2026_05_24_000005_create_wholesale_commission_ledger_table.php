<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wholesale_commission_ledger')) {
            return;
        }

        Schema::create('wholesale_commission_ledger', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('enquiry_id')->nullable()->constrained('wholesale_enquiries')->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('wholesale_quotes')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->string('order_type')->default('wholesale'); // retail | wholesale
            $table->decimal('subtotal', 10, 2);
            $table->string('commission_type');
            $table->decimal('commission_value_snapshot', 8, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('vendor_earning', 10, 2);

            // COD tracking: admin/vendor/courier
            $table->string('cod_collected_by')->nullable();

            // Admin settlement
            $table->string('settlement_status', 50)->default('pending'); // pending | settled
            $table->timestamp('settled_at')->nullable();
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'settlement_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_commission_ledger');
    }
};
