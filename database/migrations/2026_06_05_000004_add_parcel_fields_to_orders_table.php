<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        // Courier/tracking columns (selected_courier_id, tracking_id, consignment_id,
        // courier_status, sent_to_courier_at, overrides) already exist from the
        // 2026_05_19 courier migration. Only the parcel-attribution + pickup link
        // are new here.
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'pickup_point_id')) {
                $table->foreignId('pickup_point_id')->nullable()->after('selected_courier_id')
                      ->constrained('vendor_pickup_points')->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'parcel_created_by')) {
                $table->string('parcel_created_by', 20)->nullable()->after('sent_to_courier_at'); // admin | vendor
            }
            if (! Schema::hasColumn('orders', 'parcel_created_by_user_id')) {
                $table->unsignedBigInteger('parcel_created_by_user_id')->nullable()->after('parcel_created_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'pickup_point_id')) {
                $table->dropConstrainedForeignId('pickup_point_id');
            }
            foreach (['parcel_created_by', 'parcel_created_by_user_id'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
