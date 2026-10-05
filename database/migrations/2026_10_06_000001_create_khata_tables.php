<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * দোকানের খাতা — a self-contained stock & ledger book for vendors who only
 * want inventory / sales / due tracking (no e-commerce). Kept apart from the
 * storefront products so nothing here can ever appear on the website.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'panel_mode')) {
                // full = e-commerce vendor panel · inventory = খাতা only
                $table->string('panel_mode', 20)->default('full')->after('status');
            }
            if (! Schema::hasColumn('vendors', 'khata_settings')) {
                $table->json('khata_settings')->nullable()->after('panel_mode');
            }
        });

        Schema::create('khata_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('category', 80)->nullable();
            $table->string('unit', 20)->default('pcs');
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('stock', 14, 3)->default(0);
            $table->decimal('low_stock_alert', 14, 3)->nullable();
            $table->string('note', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['vendor_id', 'name']);
        });

        Schema::create('khata_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('phone', 20)->nullable();
            $table->string('address', 300)->nullable();
            $table->string('type', 20)->default('customer'); // customer | supplier
            // Opening balance: + they owe us (পাওনা), − we owe them (বকেয়া)
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->date('opening_date')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->index(['vendor_id', 'name']);
        });

        Schema::create('khata_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('khata_parties')->nullOnDelete();
            // sale | purchase | payment_in | payment_out | expense | stock_in | stock_out
            $table->string('type', 20);
            $table->unsignedInteger('number')->nullable();
            $table->date('date');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->string('extra_label', 60)->nullable();
            $table->decimal('extra_charge', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('paid', 14, 2)->default(0);      // paid at the time (sale/purchase) or the payment amount
            $table->string('payment_mode', 20)->default('cash'); // cash | bkash | nagad | bank | credit
            $table->string('category', 80)->nullable();        // expense category
            $table->string('note', 500)->nullable();
            $table->string('share_token', 40)->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['vendor_id', 'type', 'date']);
            $table->index(['vendor_id', 'party_id']);
        });

        Schema::create('khata_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('khata_transactions')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('khata_items')->nullOnDelete();
            $table->string('name', 150);
            $table->string('unit', 20)->nullable();
            $table->decimal('quantity', 14, 3);
            $table->decimal('price', 12, 2);
            $table->decimal('cost', 12, 2)->default(0); // purchase price snapshot → profit
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('khata_transaction_items');
        Schema::dropIfExists('khata_transactions');
        Schema::dropIfExists('khata_parties');
        Schema::dropIfExists('khata_items');
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['panel_mode', 'khata_settings']);
        });
    }
};
