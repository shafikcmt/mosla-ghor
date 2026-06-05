<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_pickup_points')) {
            return;
        }

        Schema::create('vendor_pickup_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();

            $table->string('pickup_name');           // label, e.g. "Main Shop"
            $table->string('contact_person_name');
            $table->string('phone', 30);
            $table->string('alternate_phone', 30)->nullable();

            $table->string('address');
            $table->string('district', 100);
            $table->string('city', 100);
            $table->string('zone_area', 100)->nullable(); // zone/area
            $table->string('postal_code', 20)->nullable();
            $table->text('note')->nullable();

            $table->boolean('is_default')->default(false);
            $table->string('status', 20)->default('active'); // active | inactive

            $table->timestamps();

            // Both columns are small (bigint + bool) — well under the key limit.
            $table->index(['vendor_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_pickup_points');
    }
};
