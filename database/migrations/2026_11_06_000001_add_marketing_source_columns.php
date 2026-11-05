<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['orders', 'wholesale_enquiries', 'paykari_combo_enquiries'];

    private array $columns = [
        'utm_source' => 100, 'utm_medium' => 100, 'utm_campaign' => 100,
        'utm_content' => 100, 'utm_term' => 100,
        'landing_path' => 255, 'referrer_host' => 100,
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($this->columns as $col => $len) {
                if (! Schema::hasColumn($table, $col)) {
                    Schema::table($table, fn (Blueprint $t) => $t->string($col, $len)->nullable());
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (array_keys($this->columns) as $col) {
                if (Schema::hasColumn($table, $col)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropColumn($col));
                }
            }
        }
    }
};
