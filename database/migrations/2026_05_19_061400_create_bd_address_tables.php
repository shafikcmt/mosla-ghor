<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bd_divisions', function (Blueprint $table) {
            $table->id();
            $table->string('source_id')->unique();
            $table->string('name');
            $table->string('bn_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bd_districts', function (Blueprint $table) {
            $table->id();
            $table->string('source_id')->unique();
            $table->foreignId('division_id')->nullable()->constrained('bd_divisions')->nullOnDelete();
            $table->string('name');
            $table->string('bn_name');
            $table->string('lat')->nullable();
            $table->string('lon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bd_upazilas', function (Blueprint $table) {
            $table->id();
            $table->string('source_id')->unique();
            $table->foreignId('district_id')->nullable()->constrained('bd_districts')->nullOnDelete();
            $table->string('name');
            $table->string('bn_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bd_unions', function (Blueprint $table) {
            $table->id();
            $table->string('source_id')->unique();
            $table->foreignId('upazila_id')->nullable()->constrained('bd_upazilas')->nullOnDelete();
            $table->string('name');
            $table->string('bn_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bd_unions');
        Schema::dropIfExists('bd_upazilas');
        Schema::dropIfExists('bd_districts');
        Schema::dropIfExists('bd_divisions');
    }
};
