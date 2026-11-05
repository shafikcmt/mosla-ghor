<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Encrypted JSON bag for provider credentials that don't fit api_key /
        // api_secret (e.g. Pathao username, password and store id).
        Schema::table('couriers', function (Blueprint $table) {
            $table->text('api_extra')->nullable()->after('api_secret');
        });

        // The seeder used to store a non-API merchant-panel URL for Pathao.
        DB::table('couriers')->where('slug', 'pathao')
            ->where('base_url', 'https://merchant.pathao.com/aladdin/api/v1')
            ->update(['base_url' => 'https://api-hermes.pathao.com']);
    }

    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn('api_extra');
        });
    }
};
