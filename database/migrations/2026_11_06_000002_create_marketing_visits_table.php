<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketing_visits')) {
            return;
        }
        // Anonymous UTM-tagged landings only. No IP, no raw user-agent, no PII.
        Schema::create('marketing_visits', function (Blueprint $table) {
            $table->id();
            $table->char('visitor_id', 36)->index();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_content', 100)->nullable();
            $table->string('utm_term', 100)->nullable();
            $table->string('landing_path', 255);
            $table->string('referrer_host', 100)->nullable();
            $table->string('device', 10);
            $table->timestamp('created_at')->nullable();

            $table->index(['utm_source', 'utm_campaign', 'created_at'], 'marketing_visits_source_campaign_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_visits');
    }
};
