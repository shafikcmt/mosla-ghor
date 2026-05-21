<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('id')
                ->constrained()->nullOnDelete();
            // pending/approved/rejected — null means admin-owned (always visible)
            $table->string('approval_status')->nullable()->after('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropColumn('approval_status');
        });
    }
};
