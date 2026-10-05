<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wholesale_enquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('wholesale_enquiries', 'contact_channel')) {
                // How the buyer wanted to talk: form (quote), whatsapp or call.
                $table->string('contact_channel', 20)->nullable()->after('business_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wholesale_enquiries', function (Blueprint $table) {
            $table->dropColumn('contact_channel');
        });
    }
};
