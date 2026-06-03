<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courier_settings')) {
            return;
        }

        Schema::create('courier_settings', function (Blueprint $table) {
            $table->id();
            // Vendor permission toggles
            $table->boolean('vendor_can_select_courier')->default(true);
            $table->boolean('vendor_can_update_tracking')->default(true);
            $table->boolean('vendor_can_mark_handover')->default(true);
            // admin_only | vendor_suggest | vendor_select
            $table->string('courier_selection_mode')->default('admin_only');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_settings');
    }
};
