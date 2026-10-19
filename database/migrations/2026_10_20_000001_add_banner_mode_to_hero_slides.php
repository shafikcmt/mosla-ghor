<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Image-only banner slides: the whole picture is one link, with an optional
     * phone-sized image. Existing slides keep the text + buttons layout. Additive + idempotent.
     */
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            if (! Schema::hasColumn('hero_slides', 'display_mode')) {
                $table->string('display_mode', 10)->default('text')->after('image_path');
            }
            if (! Schema::hasColumn('hero_slides', 'mobile_image_path')) {
                $table->string('mobile_image_path')->nullable()->after('display_mode');
            }
            if (! Schema::hasColumn('hero_slides', 'link_url')) {
                $table->string('link_url', 300)->nullable()->after('mobile_image_path');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep banner settings on rollback.
    }
};
