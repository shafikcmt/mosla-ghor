<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Meta Pixel settings. Platform settings live in their own one-row table (not
     * website_settings, which is loaded into views wholesale — CAPI tokens will be
     * added here in Phase B). Vendor settings are 1:1 in a separate table so the
     * mass-assigned `vendors` model never carries pixel state or secrets.
     */
    public function up(): void
    {
        if (! Schema::hasTable('marketing_settings')) {
            Schema::create('marketing_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('pixel_enabled')->default(false);
                $table->json('pixel_ids')->nullable();
                $table->string('test_event_code', 30)->nullable();
                $table->string('platform_pixel_scope', 10)->default('all'); // all | own
                $table->boolean('track_admin_users')->default(false);
                $table->boolean('vendor_pixels_enabled')->default(false);
                $table->boolean('vendor_capi_allowed')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vendor_marketing_settings')) {
            Schema::create('vendor_marketing_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->unique()->constrained('vendors')->cascadeOnDelete();
                $table->boolean('pixel_enabled')->default(false);
                $table->string('pixel_id', 20)->nullable();
                $table->string('test_event_code', 30)->nullable();
                $table->boolean('admin_blocked')->default(false);
                $table->timestamp('last_event_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_marketing_settings');
        Schema::dropIfExists('marketing_settings');
    }
};
