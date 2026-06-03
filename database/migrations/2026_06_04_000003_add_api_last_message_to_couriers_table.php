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
            if (! Schema::hasColumn('couriers', 'courier_api_last_message')) {
                // Human-readable result of the last test/send (success or failure).
                $table->string('courier_api_last_message', 1000)->nullable()->after('courier_api_last_error');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('couriers')) {
            return;
        }

        Schema::table('couriers', function (Blueprint $table) {
            if (Schema::hasColumn('couriers', 'courier_api_last_message')) {
                $table->dropColumn('courier_api_last_message');
            }
        });
    }
};
