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
        Schema::table('attendance_locations', function (Blueprint $table) {
            $table->string('check_type', 20)->default('radius')->after('status'); // 'radius' atau 'polygon'
            $table->json('polygon_coordinates')->nullable()->after('check_type'); // Array titik koordinat polygon [[lat, lng], ...]
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_locations', function (Blueprint $table) {
            $table->dropColumn(['check_type', 'polygon_coordinates']);
        });
    }
};
