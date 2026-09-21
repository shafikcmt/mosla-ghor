<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 1) Fix guest-quote crash: wholesale_enquiries.customer_id was already made
     *    nullable for guests, but wholesale_quotes.customer_id was left NOT NULL —
     *    createFromRequest() copies the (null) enquiry customer_id straight through
     *    and the insert fails. Relax it to match (same raw-ALTER pattern as the
     *    earlier vendor_id relax). FK stays in place; NULL allowed alongside it.
     * 2) Capture an optional contact email on the enquiry (guest reply channel).
     * Additive + idempotent; no data loss.
     */
    public function up(): void
    {
        Schema::table('wholesale_enquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('wholesale_enquiries', 'customer_email')) {
                $table->string('customer_email', 150)->nullable()->after('customer_whatsapp');
            }
        });

        // Relax the quotes customer_id NOT NULL so admin/vendor quotes on guest
        // enquiries can be created. Raw ALTER (no doctrine/dbal). Idempotent.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wholesale_quotes MODIFY customer_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Non-destructive: keep nullable customer_id + customer_email so guest
        // enquiries/quotes are never lost on rollback.
    }
};
