<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow guest (not-logged-in) wholesale enquiries: customer_id becomes nullable
     * and we add an optional quantity_unit. Additive + idempotent; no data loss.
     */
    public function up(): void
    {
        Schema::table('wholesale_enquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('wholesale_enquiries', 'quantity_unit')) {
                $table->string('quantity_unit', 20)->nullable()->after('quantity_kg');
            }
        });

        // Relax the customer_id NOT NULL so guests can submit. Raw ALTER (no doctrine/dbal).
        // The FK stays in place; NULL is allowed alongside it. Idempotent.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wholesale_enquiries MODIFY customer_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Non-destructive: keep nullable customer_id + quantity_unit so guest
        // enquiries are never lost on rollback.
    }
};
