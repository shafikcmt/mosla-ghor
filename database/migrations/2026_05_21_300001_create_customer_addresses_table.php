<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50)->default('বাড়ি');
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->string('division_name', 80)->nullable();
            $table->string('district_name', 80)->nullable();
            $table->string('upazila_name', 80)->nullable();
            $table->string('union_name', 80)->nullable();
            $table->text('full_address');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
