<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_conversion_events', function (Blueprint $table) {
            $table->id();
            $table->string('pixel_id', 20);
            $table->string('event_name', 40);
            $table->string('event_id', 100);
            $table->unsignedBigInteger('event_time');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->text('payload'); // Laravel encrypted array; never contains a token.
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('queued_until')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->uuid('claim_token')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('last_error', 80)->nullable();
            $table->timestamps();
            $table->unique(['pixel_id', 'event_name', 'event_id'], 'meta_conversion_identity_unique');
            $table->index(['status', 'next_attempt_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_conversion_events');
    }
};
