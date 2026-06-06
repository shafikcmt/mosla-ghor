<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('auth_otps')) {
            return;
        }

        Schema::create('auth_otps', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 150);                 // normalized phone or email
            $table->string('identifier_type', 10);             // phone | email
            $table->string('channel', 10);                     // sms | whatsapp | email
            $table->string('otp_hash');                        // hashed, never plain
            $table->string('purpose', 30);                     // login | register | verify_phone | verify_email | password_reset
            $table->string('user_type', 20)->nullable();       // customer | vendor | admin

            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(5);

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['identifier', 'purpose']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_otps');
    }
};
