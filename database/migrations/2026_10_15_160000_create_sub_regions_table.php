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
        Schema::create('sub_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('nama_sub_wilayah'); // e.g. Kota Bandung, Kota Surabaya, Kota Pekanbaru
            $table->string('kode_sub_wilayah')->unique(); // e.g. KOTA-BDG-01
            $table->string('tipe')->default('Kota'); // Kota / Kabupaten
            $table->string('status')->default('Aktif'); // Aktif / Non-Aktif
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_regions');
    }
};
