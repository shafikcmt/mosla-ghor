<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesale_commission_settings', function (Blueprint $table) {
            $table->id();

            // scope: global | vendor | category
            $table->string('scope')->default('global');
            // scope_id = vendor_id when scope=vendor; null for global
            $table->unsignedBigInteger('scope_id')->nullable();

            // applies_to: wholesale | retail | both
            $table->string('applies_to')->default('wholesale');

            $table->string('commission_type')->default('percentage'); // percentage | fixed
            $table->decimal('commission_value', 8, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['scope', 'scope_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_commission_settings');
    }
};
