<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            // SHA-256 of normalized Unicode text: independent of DB collation.
            $table->char('normalized_key', 64)->unique();
            $table->timestamps();
        });
        Schema::create('product_tag', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'tag_id']);
        });
        Schema::table('product_variants', function (Blueprint $table) {
            $table->json('attributes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('tags');
        Schema::table('product_variants', fn (Blueprint $table) => $table->dropColumn('attributes'));
    }
};
