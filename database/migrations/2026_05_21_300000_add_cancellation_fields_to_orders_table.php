<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('cancellation_reason')->nullable()->after('order_status');
            $table->string('cancelled_by', 20)->nullable()->after('cancellation_reason'); // 'customer' | 'admin'
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancellation_reason', 'cancelled_by', 'cancelled_at']);
        });
    }
};
