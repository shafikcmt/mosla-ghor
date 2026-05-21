<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('shop_name');
            $table->string('slug')->unique();
            $table->string('owner_name');
            $table->string('phone', 20);
            $table->string('email')->unique();
            $table->text('address')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('business_type')->nullable();
            $table->string('kyc_document')->nullable();
            $table->json('payment_info')->nullable();
            $table->string('commission_type')->nullable();  // percentage / fixed
            $table->decimal('commission_value', 8, 2)->nullable();
            $table->boolean('product_auto_approve')->default(false);
            $table->string('status')->default('pending');   // pending/approved/suspended/rejected
            $table->boolean('is_active')->default(true);
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
