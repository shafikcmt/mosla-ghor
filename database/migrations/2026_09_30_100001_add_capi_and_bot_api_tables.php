<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase B: server-side Meta Conversions API (platform pixel) + the Bot API that
     * lets the separate social-media automation app read catalogue/order status and
     * push Messenger/comment leads. Tokens are stored hashed (bot) or encrypted (CAPI).
     */
    public function up(): void
    {
        Schema::table('marketing_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('marketing_settings', 'capi_enabled')) {
                $table->boolean('capi_enabled')->default(false);
                $table->text('capi_access_token')->nullable(); // encrypted cast
                $table->timestamp('capi_last_success_at')->nullable();
                $table->string('capi_last_error', 500)->nullable();
                $table->timestamp('capi_last_error_at')->nullable();
            }
        });

        if (! Schema::hasTable('bot_api_tokens')) {
            Schema::create('bot_api_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->char('token_hash', 64)->unique();
                $table->json('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bot_leads')) {
            Schema::create('bot_leads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bot_api_token_id')->nullable()->constrained('bot_api_tokens')->nullOnDelete();
                $table->string('source', 20)->default('other'); // messenger | facebook_comment | whatsapp | instagram | other
                $table->string('external_ref', 100)->nullable(); // bot's own id, for idempotent retries
                $table->string('page_name', 100)->nullable();
                $table->string('customer_name', 100)->nullable();
                $table->string('phone', 20);
                $table->text('message');
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('quantity', 50)->nullable();
                $table->string('address', 500)->nullable();
                $table->string('status', 20)->default('new'); // new | contacted | converted | closed
                $table->text('admin_note')->nullable();
                $table->timestamps();

                $table->unique(['bot_api_token_id', 'external_ref']);
                $table->index(['status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_leads');
        Schema::dropIfExists('bot_api_tokens');
        Schema::table('marketing_settings', function (Blueprint $table) {
            foreach (['capi_enabled', 'capi_access_token', 'capi_last_success_at', 'capi_last_error', 'capi_last_error_at'] as $col) {
                if (Schema::hasColumn('marketing_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
