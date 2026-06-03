<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('couriers')) {
            return;
        }

        Schema::table('couriers', function (Blueprint $table) {
            if (! Schema::hasColumn('couriers', 'courier_api_last_checked_at')) {
                $table->timestamp('courier_api_last_checked_at')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('couriers', 'courier_api_last_status')) {
                // 'success' | 'failed'
                $table->string('courier_api_last_status')->nullable()->after('courier_api_last_checked_at');
            }
            if (! Schema::hasColumn('couriers', 'courier_api_last_error')) {
                $table->text('courier_api_last_error')->nullable()->after('courier_api_last_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('couriers')) {
            return;
        }

        Schema::table('couriers', function (Blueprint $table) {
            foreach (['courier_api_last_checked_at', 'courier_api_last_status', 'courier_api_last_error'] as $col) {
                if (Schema::hasColumn('couriers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
