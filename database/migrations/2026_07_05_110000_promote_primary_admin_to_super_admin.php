<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure at least one super admin exists so permanent order deletion keeps
     * working after deploy. Promotes the seeded admin (or the earliest admin)
     * only when no super admin is present yet.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role') || ! Schema::hasColumn('users', 'is_admin')) {
            return;
        }

        if (DB::table('users')->where('role', 'super_admin')->exists()) {
            return;
        }

        $primary = DB::table('users')
            ->where('is_admin', true)
            ->orderByRaw("CASE WHEN email = 'admin@mosla.test' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        if ($primary) {
            DB::table('users')->where('id', $primary->id)->update(['role' => 'super_admin']);
        }
    }

    public function down(): void
    {
        // Intentionally left as a no-op: demoting the super admin could lock the
        // feature and there is no reliable prior value to restore to.
    }
};
