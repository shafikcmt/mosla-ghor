<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('enquiry_id')
                  ->nullable()
                  ->after('customer_id')
                  ->constrained('wholesale_enquiries')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['enquiry_id']);
            $table->dropColumn('enquiry_id');
        });
    }
};
