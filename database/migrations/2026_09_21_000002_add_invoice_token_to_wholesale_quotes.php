<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Public, token-addressed wholesale quote invoice — same pattern as
     * orders.invoice_token. Lets a guest (who never set a password) open their
     * quote via a secret link, no login required. Additive + idempotent.
     */
    public function up(): void
    {
        Schema::table('wholesale_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('wholesale_quotes', 'invoice_token')) {
                $table->string('invoice_token', 64)->nullable()->unique()->after('status');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep invoice_token so existing quote links never break.
    }
};
