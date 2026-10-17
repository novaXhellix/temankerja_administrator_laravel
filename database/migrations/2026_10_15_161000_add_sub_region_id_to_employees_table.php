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
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('sub_region_id')->nullable()->after('region_id')->constrained('sub_regions')->nullOnDelete();
            $table->string('sub_wilayah')->nullable()->after('wilayah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['sub_region_id']);
            $table->dropColumn(['sub_region_id', 'sub_wilayah']);
        });
    }
};
