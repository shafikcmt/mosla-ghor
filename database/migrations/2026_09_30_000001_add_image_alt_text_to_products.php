<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Image alt text for SEO/accessibility. All nullable: empty = product-name fallback. */
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            foreach (['main_image_alt' => 'string', 'og_image_alt' => 'string', 'gallery_alts' => 'json'] as $column => $type) {
                if (! Schema::hasColumn('products', $column)) {
                    Schema::table('products', function (Blueprint $table) use ($column, $type) {
                        $type === 'json' ? $table->json($column)->nullable() : $table->string($column, 255)->nullable();
                    });
                }
            }
        }
        if (Schema::hasTable('product_variants') && ! Schema::hasColumn('product_variants', 'image_alt')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->string('image_alt', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            $existing = array_values(array_filter(['main_image_alt', 'og_image_alt', 'gallery_alts'], fn ($c) => Schema::hasColumn('products', $c)));
            if ($existing) {
                Schema::table('products', fn (Blueprint $table) => $table->dropColumn($existing));
            }
        }
        if (Schema::hasTable('product_variants') && Schema::hasColumn('product_variants', 'image_alt')) {
            Schema::table('product_variants', fn (Blueprint $table) => $table->dropColumn('image_alt'));
        }
    }
};
