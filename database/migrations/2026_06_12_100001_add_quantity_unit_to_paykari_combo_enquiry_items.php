<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paykari_combo_enquiry_items', function (Blueprint $table) {
            // Wholesale unit for the quantity (kg / bag / carton). Existing rows
            // default to kg so old data keeps its meaning.
            $table->string('quantity_unit', 20)->default('kg')->after('quantity_kg');
        });
    }

    public function down(): void
    {
        Schema::table('paykari_combo_enquiry_items', function (Blueprint $table) {
            $table->dropColumn('quantity_unit');
        });
    }
};
