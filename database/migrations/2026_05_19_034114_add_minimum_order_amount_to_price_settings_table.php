<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_settings', function (Blueprint $table) {
            $table->decimal('minimum_order_amount', 10, 2)->default(300)->after('default_packaging_cost');
        });
    }

    public function down(): void
    {
        Schema::table('price_settings', function (Blueprint $table) {
            $table->dropColumn('minimum_order_amount');
        });
    }
};
