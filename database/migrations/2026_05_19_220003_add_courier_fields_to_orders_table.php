<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('selected_courier_id')->nullable()->after('combo_id')->constrained('couriers')->nullOnDelete();
            $table->foreignId('suggested_courier_id')->nullable()->after('selected_courier_id')->constrained('couriers')->nullOnDelete();
            $table->foreignId('delivery_rate_id')->nullable()->after('suggested_courier_id')->constrained('delivery_rates')->nullOnDelete();
            $table->string('delivery_zone_type')->nullable()->after('delivery_rate_id');
            $table->integer('weight_gram')->nullable()->after('delivery_zone_type');
            $table->decimal('courier_cost', 10, 2)->nullable()->after('weight_gram');
            $table->decimal('cod_charge', 10, 2)->nullable()->after('courier_cost');
            $table->string('courier_status')->nullable()->after('cod_charge');
            $table->string('tracking_id')->nullable()->after('courier_status');
            $table->string('consignment_id')->nullable()->after('tracking_id');
            $table->text('courier_note')->nullable()->after('consignment_id');
            $table->timestamp('sent_to_courier_at')->nullable()->after('courier_note');
            $table->timestamp('delivered_at')->nullable()->after('sent_to_courier_at');
            $table->timestamp('returned_at')->nullable()->after('delivered_at');
            $table->boolean('delivery_charge_overridden')->default(false)->after('returned_at');
            $table->boolean('courier_cost_overridden')->default(false)->after('delivery_charge_overridden');
            $table->boolean('zone_overridden')->default(false)->after('courier_cost_overridden');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('selected_courier_id');
            $table->dropConstrainedForeignId('suggested_courier_id');
            $table->dropConstrainedForeignId('delivery_rate_id');
            $table->dropColumn([
                'delivery_zone_type', 'weight_gram', 'courier_cost', 'cod_charge',
                'courier_status', 'tracking_id', 'consignment_id', 'courier_note',
                'sent_to_courier_at', 'delivered_at', 'returned_at',
                'delivery_charge_overridden', 'courier_cost_overridden', 'zone_overridden',
            ]);
        });
    }
};
