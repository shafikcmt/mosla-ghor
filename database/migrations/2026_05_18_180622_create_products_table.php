<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name_bn');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->string('main_image')->nullable();
            $table->json('gallery_images')->nullable();
            $table->string('video_url')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('retail_price_1kg', 10, 2);
            $table->decimal('wholesale_price_1kg', 10, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
