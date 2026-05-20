<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('combo_items', function (Blueprint $table) {
            $table->string('sell_type', 20)->default('retail')->after('combo_id');
            $table->unsignedBigInteger('product_price_id')->nullable()->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('combo_items', function (Blueprint $table) {
            $table->dropColumn(['sell_type', 'product_price_id']);
        });
    }
};
