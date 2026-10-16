<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('marketing_settings', 'test_event_code_expires_at')) {
                $table->timestamp('test_event_code_expires_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketing_settings', function (Blueprint $table) {
            if (Schema::hasColumn('marketing_settings', 'test_event_code_expires_at')) {
                $table->dropColumn('test_event_code_expires_at');
            }
        });
    }
};
