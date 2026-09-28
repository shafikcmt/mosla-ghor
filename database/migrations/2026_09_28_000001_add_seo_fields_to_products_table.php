<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Per-product SEO overrides. All nullable: empty means "use the auto value". */
    private array $columns = [
        'meta_title'       => 70,
        'meta_description' => 170,
        'meta_keywords'    => 255,
        'og_image'         => 255,
        'canonical_url'    => 255,
        'meta_robots'      => 30,
    ];

    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        foreach ($this->columns as $column => $length) {
            if (! Schema::hasColumn('products', $column)) {
                Schema::table('products', function (Blueprint $table) use ($column, $length) {
                    $table->string($column, $length)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $existing = array_values(array_filter(array_keys($this->columns), fn ($c) => Schema::hasColumn('products', $c)));
        if ($existing) {
            Schema::table('products', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
