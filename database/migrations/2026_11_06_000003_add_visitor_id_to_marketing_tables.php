<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['orders', 'wholesale_enquiries', 'paykari_combo_enquiries'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'visitor_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->char('visitor_id', 36)->nullable()->index());
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'visitor_id')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->dropIndex($table.'_visitor_id_index');
                    $t->dropColumn('visitor_id');
                });
            }
        }
    }
};
