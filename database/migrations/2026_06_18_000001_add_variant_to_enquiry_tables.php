<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a selected-variant snapshot to the wholesale + combo enquiry records.
     * Non-destructive: both columns are nullable so existing rows stay valid.
     */
    public function up(): void
    {
        if (Schema::hasTable('wholesale_enquiries')) {
            Schema::table('wholesale_enquiries', function (Blueprint $table) {
                if (! Schema::hasColumn('wholesale_enquiries', 'product_variant_id')) {
                    $table->unsignedBigInteger('product_variant_id')->nullable()->after('product_id');
                }
                if (! Schema::hasColumn('wholesale_enquiries', 'variant_name')) {
                    $table->string('variant_name')->nullable()->after('product_name');
                }
            });
        }

        if (Schema::hasTable('paykari_combo_enquiry_items')) {
            Schema::table('paykari_combo_enquiry_items', function (Blueprint $table) {
                if (! Schema::hasColumn('paykari_combo_enquiry_items', 'product_variant_id')) {
                    $table->unsignedBigInteger('product_variant_id')->nullable()->after('product_id');
                }
                if (! Schema::hasColumn('paykari_combo_enquiry_items', 'variant_name')) {
                    $table->string('variant_name')->nullable()->after('product_name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wholesale_enquiries')) {
            Schema::table('wholesale_enquiries', function (Blueprint $table) {
                if (Schema::hasColumn('wholesale_enquiries', 'product_variant_id')) {
                    $table->dropColumn('product_variant_id');
                }
                if (Schema::hasColumn('wholesale_enquiries', 'variant_name')) {
                    $table->dropColumn('variant_name');
                }
            });
        }

        if (Schema::hasTable('paykari_combo_enquiry_items')) {
            Schema::table('paykari_combo_enquiry_items', function (Blueprint $table) {
                if (Schema::hasColumn('paykari_combo_enquiry_items', 'product_variant_id')) {
                    $table->dropColumn('product_variant_id');
                }
                if (Schema::hasColumn('paykari_combo_enquiry_items', 'variant_name')) {
                    $table->dropColumn('variant_name');
                }
            });
        }
    }
};
