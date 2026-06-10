<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * IndiaMART-style quote terms + allow admin-submitted quotes (no vendor).
     * Additive + idempotent; no data loss.
     */
    public function up(): void
    {
        Schema::table('wholesale_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('wholesale_quotes', 'advance_percentage')) {
                $table->decimal('advance_percentage', 5, 2)->nullable()->after('advance_required');
            }
            if (! Schema::hasColumn('wholesale_quotes', 'delivery_time')) {
                $table->string('delivery_time', 100)->nullable()->after('advance_percentage');
            }
            if (! Schema::hasColumn('wholesale_quotes', 'validity_days')) {
                $table->unsignedInteger('validity_days')->nullable()->after('valid_until');
            }
        });

        // Admin can submit a quote when a product has no assigned vendor.
        // Relax vendor_id NOT NULL; FK stays in place (NULL allowed alongside it). Raw ALTER, no doctrine/dbal.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wholesale_quotes MODIFY vendor_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Non-destructive: keep the new columns + nullable vendor_id so admin
        // quotes are never lost on rollback.
    }
};
