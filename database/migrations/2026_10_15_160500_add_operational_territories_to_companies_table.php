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
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('status')->constrained('regions')->nullOnDelete();
            $table->foreignId('sub_region_id')->nullable()->after('region_id')->constrained('sub_regions')->nullOnDelete();
            $table->string('wilayah_operasional')->nullable()->after('sub_region_id'); // Menyimpan nama provinsi
            $table->string('sub_wilayah')->nullable()->after('wilayah_operasional'); // Menyimpan nama kota/kabupaten
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['sub_region_id']);
            $table->dropColumn(['region_id', 'sub_region_id', 'wilayah_operasional', 'sub_wilayah']);
        });
    }
};
