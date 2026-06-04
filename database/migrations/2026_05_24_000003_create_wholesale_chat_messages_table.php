<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesale_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')->constrained('wholesale_enquiries')->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('wholesale_quotes')->nullOnDelete();

            // sender_type: customer | vendor | admin
            $table->string('sender_type');
            // sender_id: customer.id | vendor.id | user.id
            $table->unsignedBigInteger('sender_id');

            $table->text('message');

            // Contact-sharing filter
            $table->boolean('is_filtered')->default(false);
            $table->string('filter_reason')->nullable();

            // Read receipts
            $table->boolean('is_read_by_customer')->default(false);
            $table->boolean('is_read_by_vendor')->default(false);
            $table->boolean('is_read_by_admin')->default(false);

            $table->timestamps();

            $table->index(['enquiry_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_chat_messages');
    }
};
