<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wholesale_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('wholesale_quotes', 'delivery_charge_mode')) {
                // fixed = amount given · later = "applicable, told at confirmation" · free
                $table->string('delivery_charge_mode', 10)->default('fixed')->after('delivery_charge');
            }
            if (! Schema::hasColumn('wholesale_quotes', 'terms')) {
                // Snapshot of the standard terms shown to the customer (list of lines).
                $table->json('terms')->nullable()->after('note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wholesale_quotes', function (Blueprint $table) {
            $table->dropColumn(['delivery_charge_mode', 'terms']);
        });
    }
};
