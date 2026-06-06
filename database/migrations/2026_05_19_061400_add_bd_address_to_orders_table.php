<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The address columns this migration manages.
     *
     * Note: this migration historically used after('delivery_location_name'),
     * but that column is created in a LATER migration (..._100002_...), so the
     * reference column does not yet exist when this runs. We therefore add the
     * columns without a positional anchor and guard each with hasColumn so the
     * migration is idempotent and safe on partially-applied MySQL schemas.
     */
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'bd_division_id')) {
                $table->unsignedBigInteger('bd_division_id')->nullable();
            }
            if (! Schema::hasColumn('orders', 'bd_district_id')) {
                $table->unsignedBigInteger('bd_district_id')->nullable();
            }
            if (! Schema::hasColumn('orders', 'bd_upazila_id')) {
                $table->unsignedBigInteger('bd_upazila_id')->nullable();
            }
            if (! Schema::hasColumn('orders', 'bd_union_id')) {
                $table->unsignedBigInteger('bd_union_id')->nullable();
            }
            if (! Schema::hasColumn('orders', 'division_name')) {
                $table->string('division_name')->nullable();
            }
            if (! Schema::hasColumn('orders', 'district_name')) {
                $table->string('district_name')->nullable();
            }
            if (! Schema::hasColumn('orders', 'upazila_name')) {
                $table->string('upazila_name')->nullable();
            }
            if (! Schema::hasColumn('orders', 'union_name')) {
                $table->string('union_name')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'bd_division_id', 'bd_district_id', 'bd_upazila_id', 'bd_union_id',
                'division_name', 'district_name', 'upazila_name', 'union_name',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
