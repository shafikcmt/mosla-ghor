<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track when a user actually set their own password. Guest wholesale accounts
     * are created with a random unguessable password; this stays NULL until the
     * customer claims the account via the set-password link. Additive + idempotent.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'password_set_at')) {
                $table->timestamp('password_set_at')->nullable()->after('password');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep password_set_at so activation history is never lost.
    }
};
