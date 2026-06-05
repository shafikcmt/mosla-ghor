<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_customers')) {
            return;
        }

        Schema::create('vendor_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('user_id')->nullable();   // linked User/Customer account if any

            $table->string('name', 150);
            $table->string('phone', 30);
            $table->string('whatsapp', 30)->nullable();
            $table->string('email', 150)->nullable();

            $table->string('address', 500)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('area', 100)->nullable();

            // Regular | Shop | Restaurant | Dealer | Retailer | Other
            $table->string('customer_type', 30)->default('Regular');

            $table->decimal('due_balance', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active');     // active | inactive

            $table->timestamps();

            $table->index(['vendor_id', 'phone']);
            $table->index('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_customers');
    }
};
