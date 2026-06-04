<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $tableName = 'wholesale_enquiries';

    public function up(): void
    {
        // Fresh install: create the table with an index-safe short `status`.
        if (! Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();

                // Enquiry details (visible to vendor)
                $table->decimal('quantity_kg', 10, 2)->default(1);
                $table->string('delivery_location');
                $table->string('business_type'); // shop/restaurant/dealer/retailer/other
                $table->text('message')->nullable();

                // Customer contact — HIDDEN FROM VENDOR, Admin only
                $table->string('customer_name');
                $table->string('customer_phone');
                $table->string('customer_whatsapp')->nullable();

                // Snapshot of product name at enquiry time
                $table->string('product_name');

                // Short length keeps composite indexes under the 1000-byte
                // key limit on shared MySQL/MariaDB (utf8mb4 = 4 bytes/char).
                $table->string('status', 50)->default('pending');
                // pending → quoted → accepted → completed | rejected | cancelled

                $table->text('vendor_note')->nullable();
                $table->text('admin_note')->nullable();
                $table->timestamps();

                $table->index(['vendor_id', 'status']);
                $table->index(['customer_id', 'status']);
            });

            return;
        }

        // Table already exists — a previous live run created it but aborted on
        // the oversized index. Repair it in place; never drop or recreate.
        $this->repairExistingTable();
    }

    /**
     * Bring an already-created (partially migrated) table up to spec without
     * touching existing rows.
     */
    private function repairExistingTable(): void
    {
        // 1. Shrink `status` to an index-safe length if it is still too long.
        if (Schema::hasColumn($this->tableName, 'status')) {
            $type = $this->columnType('status');
            if ($type !== null && stripos($type, 'varchar(50)') === false) {
                Schema::table($this->tableName, function (Blueprint $table) {
                    $table->string('status', 50)->default('pending')->change();
                });
            }
        }

        // 2. Defensive: add any scalar column a partial create might have missed.
        //    (A successful CREATE TABLE is atomic, so this is normally a no-op.)
        Schema::table($this->tableName, function (Blueprint $table) {
            if (! Schema::hasColumn($this->tableName, 'quantity_kg'))       $table->decimal('quantity_kg', 10, 2)->default(1);
            if (! Schema::hasColumn($this->tableName, 'delivery_location')) $table->string('delivery_location')->nullable();
            if (! Schema::hasColumn($this->tableName, 'business_type'))     $table->string('business_type')->nullable();
            if (! Schema::hasColumn($this->tableName, 'message'))           $table->text('message')->nullable();
            if (! Schema::hasColumn($this->tableName, 'customer_name'))     $table->string('customer_name')->nullable();
            if (! Schema::hasColumn($this->tableName, 'customer_phone'))    $table->string('customer_phone')->nullable();
            if (! Schema::hasColumn($this->tableName, 'customer_whatsapp')) $table->string('customer_whatsapp')->nullable();
            if (! Schema::hasColumn($this->tableName, 'product_name'))      $table->string('product_name')->nullable();
            if (! Schema::hasColumn($this->tableName, 'status'))            $table->string('status', 50)->default('pending');
            if (! Schema::hasColumn($this->tableName, 'vendor_note'))       $table->text('vendor_note')->nullable();
            if (! Schema::hasColumn($this->tableName, 'admin_note'))        $table->text('admin_note')->nullable();
        });

        // 3. Add the indexes only if they are not already present (no duplicates).
        if (! $this->indexExists('wholesale_enquiries_vendor_id_status_index')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->index(['vendor_id', 'status']);
            });
        }

        if (! $this->indexExists('wholesale_enquiries_customer_id_status_index')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->index(['customer_id', 'status']);
            });
        }
    }

    private function indexExists(string $indexName): bool
    {
        $rows = DB::select(
            "SHOW INDEX FROM `{$this->tableName}` WHERE Key_name = ?",
            [$indexName]
        );

        return ! empty($rows);
    }

    private function columnType(string $column): ?string
    {
        $rows = DB::select(
            "SHOW COLUMNS FROM `{$this->tableName}` WHERE Field = ?",
            [$column]
        );

        return $rows[0]->Type ?? null;
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
