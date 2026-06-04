<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paykari_combo_enquiry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_enquiry_id')
                  ->constrained('paykari_combo_enquiries')
                  ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('product_name'); // snapshot at enquiry time
            $table->decimal('quantity_kg', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paykari_combo_enquiry_items');
    }
};
