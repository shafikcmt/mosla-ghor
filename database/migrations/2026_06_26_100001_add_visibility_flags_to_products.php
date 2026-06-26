<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Independent retail / wholesale visibility. Previously a product was EITHER
     * retail OR wholesale (single `is_wholesale` flag). These two flags decouple
     * the two listings so a product can appear in retail, wholesale, or both.
     * Additive + idempotent — existing wholesale columns are left untouched.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'show_in_retail')) {
                $table->boolean('show_in_retail')->default(true)->after('is_active');
            }
            if (! Schema::hasColumn('products', 'show_in_wholesale')) {
                $table->boolean('show_in_wholesale')->default(false)->after('show_in_retail');
            }
        });

        // Backfill existing rows: active products are visible in retail; wholesale
        // visibility mirrors the legacy is_wholesale flag.
        if (Schema::hasColumn('products', 'is_wholesale')) {
            DB::table('products')->update([
                'show_in_retail'    => DB::raw('CASE WHEN is_active = 1 THEN 1 ELSE 0 END'),
                'show_in_wholesale' => DB::raw('is_wholesale'),
            ]);
        }
    }

    public function down(): void
    {
        // Non-destructive: keep columns so no visibility config is lost on rollback.
    }
};
