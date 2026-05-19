<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('inside_dhaka_charge', 10, 2)->default(60);
            $table->decimal('outside_dhaka_charge', 10, 2)->default(120);
            $table->decimal('free_delivery_minimum_amount', 10, 2)->nullable();
            $table->boolean('enable_free_delivery')->default(false);
            $table->text('delivery_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_settings');
    }
};
