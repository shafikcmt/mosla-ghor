<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shop staff for দোকানের খাতা: a staff member is a user (role vendor) linked to the
     * owner's vendor, with a list of allowed tasks. Additive + idempotent.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'staff_vendor_id')) {
                $table->foreignId('staff_vendor_id')->nullable()->after('role')->constrained('vendors')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('users', 'staff_permissions')) {
                $table->json('staff_permissions')->nullable()->after('staff_vendor_id');
            }
            if (! Schema::hasColumn('users', 'staff_active')) {
                $table->boolean('staff_active')->default(true)->after('staff_permissions');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep staff accounts on rollback.
    }
};
