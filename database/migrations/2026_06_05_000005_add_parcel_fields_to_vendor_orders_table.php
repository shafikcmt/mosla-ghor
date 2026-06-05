<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_orders')) {
            return;
        }

        // fulfillment_status, courier_id, courier_name, tracking_number, vendor_note,
        // ready_at, handed_to_courier_at already exist from the 2026_05_21 fulfillment
        // migration. Add the parcel/courier-tracking columns that are new.
        Schema::table('vendor_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_orders', 'pickup_point_id')) {
                $table->foreignId('pickup_point_id')->nullable()->after('courier_id')
                      ->constrained('vendor_pickup_points')->nullOnDelete();
            }
            if (! Schema::hasColumn('vendor_orders', 'consignment_id')) {
                $table->string('consignment_id')->nullable()->after('tracking_number');
            }
            if (! Schema::hasColumn('vendor_orders', 'courier_status')) {
                $table->string('courier_status')->nullable()->after('consignment_id');
            }
            if (! Schema::hasColumn('vendor_orders', 'courier_note')) {
                $table->text('courier_note')->nullable()->after('courier_status');
            }
            if (! Schema::hasColumn('vendor_orders', 'sent_to_courier_at')) {
                $table->timestamp('sent_to_courier_at')->nullable()->after('handed_to_courier_at');
            }
            if (! Schema::hasColumn('vendor_orders', 'parcel_created_by')) {
                $table->string('parcel_created_by', 20)->nullable()->after('sent_to_courier_at'); // admin | vendor
            }
            if (! Schema::hasColumn('vendor_orders', 'parcel_created_by_user_id')) {
                $table->unsignedBigInteger('parcel_created_by_user_id')->nullable()->after('parcel_created_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vendor_orders')) {
            return;
        }

        Schema::table('vendor_orders', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_orders', 'pickup_point_id')) {
                $table->dropConstrainedForeignId('pickup_point_id');
            }
            foreach (['consignment_id', 'courier_status', 'courier_note', 'sent_to_courier_at', 'parcel_created_by', 'parcel_created_by_user_id'] as $col) {
                if (Schema::hasColumn('vendor_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
