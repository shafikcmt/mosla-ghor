<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('bkash_number', 20)->nullable();
            $table->string('rocket_number', 20)->nullable();
            $table->string('nagad_number', 20)->nullable();
            $table->text('payment_instruction')->nullable();
            $table->boolean('cash_on_delivery_enabled')->default(true);
            $table->boolean('bkash_enabled')->default(false);
            $table->boolean('rocket_enabled')->default(false);
            $table->boolean('nagad_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
