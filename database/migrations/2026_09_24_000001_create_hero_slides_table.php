<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('subtitle')->nullable();
            $table->string('eyebrow', 100)->nullable();
            $table->string('image_path', 500);
            $table->string('primary_label', 60)->nullable();
            $table->string('primary_url', 300)->nullable();
            $table->string('secondary_label', 60)->nullable();
            $table->string('secondary_url', 300)->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};
