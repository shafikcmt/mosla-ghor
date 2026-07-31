<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single-row SMTP settings managed from the admin panel (mirrors the courier
     * API-settings pattern). Additive + idempotent: safe to run on production.
     * No existing table/column is modified or dropped.
     */
    public function up(): void
    {
        if (Schema::hasTable('mail_settings')) {
            return;
        }

        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->string('driver')->default('smtp');       // smtp | log
            $table->string('host')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();            // encrypted at rest (model cast)
            $table->string('encryption')->nullable();        // tls | ssl | null
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->boolean('is_enabled')->default(false);   // when false, .env config is used
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_settings');
    }
};
