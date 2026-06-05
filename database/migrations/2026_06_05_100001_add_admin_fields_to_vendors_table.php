<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendors')) {
            return;
        }

        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'district'))       $table->string('district', 100)->nullable()->after('address');
            if (! Schema::hasColumn('vendors', 'city'))           $table->string('city', 100)->nullable()->after('district');
            if (! Schema::hasColumn('vendors', 'trade_license'))  $table->string('trade_license', 100)->nullable()->after('city');
            if (! Schema::hasColumn('vendors', 'nid'))            $table->string('nid', 50)->nullable()->after('trade_license');
            if (! Schema::hasColumn('vendors', 'approved_at'))    $table->timestamp('approved_at')->nullable();
            if (! Schema::hasColumn('vendors', 'approved_by'))    $table->unsignedBigInteger('approved_by')->nullable();
            if (! Schema::hasColumn('vendors', 'suspended_at'))   $table->timestamp('suspended_at')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vendors')) {
            return;
        }

        Schema::table('vendors', function (Blueprint $table) {
            foreach (['district', 'city', 'trade_license', 'nid', 'approved_at', 'approved_by', 'suspended_at'] as $col) {
                if (Schema::hasColumn('vendors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
