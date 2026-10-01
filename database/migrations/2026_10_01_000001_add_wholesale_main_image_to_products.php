<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional separate cover photo for the পাইকারি (wholesale) showcase. When empty the
     * normal main_image is used everywhere, so existing products are unchanged. The
     * gallery stays shared between retail and wholesale. Additive + idempotent.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'wholesale_main_image')) {
                $table->string('wholesale_main_image')->nullable()->after('main_image');
            }
            if (! Schema::hasColumn('products', 'wholesale_main_image_alt')) {
                $table->string('wholesale_main_image_alt')->nullable()->after('wholesale_main_image');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep columns so no uploaded cover photo is lost on rollback.
    }
};
