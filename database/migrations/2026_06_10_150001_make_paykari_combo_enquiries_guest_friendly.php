<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow guest (not-logged-in) Paykari combo enquiries by relaxing the
     * customer_id NOT NULL. Idempotent; FK stays; no data loss.
     */
    public function up(): void
    {
        if (Schema::hasTable('paykari_combo_enquiries') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE paykari_combo_enquiries MODIFY customer_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Non-destructive: keep nullable so guest enquiries are not lost on rollback.
    }
};
