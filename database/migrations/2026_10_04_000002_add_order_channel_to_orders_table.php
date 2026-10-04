<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where an admin-entered order came from (phone, whatsapp, messenger, …).
     * Null for regular website checkout orders.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'order_channel')) {
                $table->string('order_channel', 30)->nullable()->after('order_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'order_channel')) {
                $table->dropColumn('order_channel');
            }
        });
    }
};
