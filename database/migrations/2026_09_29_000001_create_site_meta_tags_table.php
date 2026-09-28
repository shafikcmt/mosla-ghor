<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-managed <head> meta tags (domain verification etc.). Structured fields
     * only — our code builds the tag. Not in website_settings, which is loaded into
     * views wholesale.
     */
    public function up(): void
    {
        if (Schema::hasTable('site_meta_tags')) {
            return;
        }

        Schema::create('site_meta_tags', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100)->nullable();
            $table->string('attribute', 10)->default('name'); // name | property
            $table->string('name', 100);
            $table->string('content', 500);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_meta_tags');
    }
};
