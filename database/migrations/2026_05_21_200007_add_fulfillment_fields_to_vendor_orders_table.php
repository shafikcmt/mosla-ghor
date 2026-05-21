<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_orders', function (Blueprint $table) {
            $table->string('fulfillment_status')->nullable()->default('pending')->after('status');
            $table->unsignedBigInteger('courier_id')->nullable()->after('fulfillment_status');
            $table->string('courier_name')->nullable()->after('courier_id');
            $table->string('tracking_number')->nullable()->after('courier_name');
            $table->text('vendor_note')->nullable()->after('tracking_number');
            $table->timestamp('ready_at')->nullable()->after('vendor_note');
            $table->timestamp('handed_to_courier_at')->nullable()->after('ready_at');

            $table->foreign('courier_id')->references('id')->on('couriers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_orders', function (Blueprint $table) {
            $table->dropForeign(['courier_id']);
            $table->dropColumn([
                'fulfillment_status', 'courier_id', 'courier_name',
                'tracking_number', 'vendor_note', 'ready_at', 'handed_to_courier_at',
            ]);
        });
    }
};
