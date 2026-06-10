<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make product_reviews usable for public product-detail reviews (guest + logged-in),
     * not just purchase-verified ones. Additive + idempotent: safe to re-run, no data loss.
     */
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('product_reviews', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('product_id');
            }
            // Private contact (phone/email) — never shown publicly.
            if (! Schema::hasColumn('product_reviews', 'customer_contact')) {
                $table->string('customer_contact')->nullable()->after('customer_name');
            }
            if (! Schema::hasColumn('product_reviews', 'title')) {
                $table->string('title')->nullable()->after('rating');
            }
            if (! Schema::hasColumn('product_reviews', 'image')) {
                $table->string('image')->nullable()->after('comment');
            }
        });

        // Relax the purchase-lock: allow reviews with no linked user/order (guest or
        // detail-page reviews). Raw ALTER because doctrine/dbal is not installed.
        // Idempotent — modifying an already-nullable column is a no-op.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE product_reviews MODIFY user_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE product_reviews MODIFY order_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: keep columns and nullability so existing
        // guest reviews are not lost. (Reverting NOT NULL could fail on null rows.)
    }
};
