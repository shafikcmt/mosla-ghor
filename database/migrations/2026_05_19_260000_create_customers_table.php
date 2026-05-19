<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile_number')->unique();
            $table->string('alternative_number')->nullable();
            $table->string('email')->nullable();
            $table->string('last_division_name')->nullable();
            $table->string('last_district_name')->nullable();
            $table->string('last_upazila_name')->nullable();
            $table->string('last_union_name')->nullable();
            $table->string('last_full_address')->nullable();
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->timestamp('last_order_at')->nullable();
            $table->boolean('accepts_marketing')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
