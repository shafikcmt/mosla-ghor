<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasColumn('orders', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            }
            if (! Schema::hasColumn('orders', 'delete_reason')) {
                $table->string('delete_reason', 500)->nullable()->after('deleted_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delete_reason')) {
                $table->dropColumn('delete_reason');
            }
            if (Schema::hasColumn('orders', 'deleted_by')) {
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn('orders', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
