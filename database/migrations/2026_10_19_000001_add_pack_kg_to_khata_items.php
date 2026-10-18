<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Weight of one packed unit, e.g. 1 কার্টন = 20 কেজি. Item default plus a per-line
     * snapshot (a bosta can be 50 kg in one purchase and 25 kg in another). Additive + idempotent.
     */
    public function up(): void
    {
        Schema::table('khata_items', function (Blueprint $table) {
            if (! Schema::hasColumn('khata_items', 'pack_kg')) {
                $table->decimal('pack_kg', 10, 3)->nullable()->after('unit');
            }
        });
        Schema::table('khata_transaction_items', function (Blueprint $table) {
            if (! Schema::hasColumn('khata_transaction_items', 'pack_kg')) {
                $table->decimal('pack_kg', 10, 3)->nullable()->after('unit');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep pack weights on rollback.
    }
};
